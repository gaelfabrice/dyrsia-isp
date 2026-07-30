<?php
/**
 * Corps du déploiement hotspot MikroTik (Send complet / login.html).
 * Inclus depuis settings.php ou HotspotDeployRunner (worker CLI).
 */
if (!isset($hotspotDeployExecuteReady) || !$hotspotDeployExecuteReady) {
    return;
}
                Mikrotik::resetMikrotikReconnectBudget($sendFullDeploy ? 28 : 12);
                if (isset($hotspotDeployProgress) && is_callable($hotspotDeployProgress)) {
                    $hotspotDeployProgress('Connexion API MikroTik…');
                }
                $client = Mikrotik::getClient($mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password'], 30, true, true);
                if (!$client) {
                    $hotspotDeployFinish(
                        'e',
                        'Connexion MikroTik impossible : compte démo ou synchronisation routeur désactivée pour cet utilisateur.'
                    );
                }
                $hotspotDeployRouterRow = is_array($mikrotik)
                    ? $mikrotik
                    : (is_object($mikrotik) && method_exists($mikrotik, 'as_array') ? $mikrotik->as_array() : null);
                Mikrotik::setMikrotikDeployRouterContext($hotspotDeployRouterRow);
                register_shutdown_function(static function () {
                    Mikrotik::clearMikrotikDeployRouterContext();
                });
                $hotspotServerName = trim((string) ($config['hotspot_name'] ?? ''));
                $fetchTs = time();
                $apiUrlForFetch = Mikrotik::resolveHotspotBackendApiUrl($config);
                if ($apiUrlForFetch === '') {
                    $hotspotDeployFinish( 'e', 'Hotspot API URL requise (ex. https://wifizones.org).');
                }
                $hotspotListenIp = $hotspotServerName !== ''
                    ? Mikrotik::getHotspotServerAddress($client, $hotspotServerName)
                    : '';

                $useLightSend = !$sendFullDeploy && $hotspotListenIp !== '';

                if (!$sendFullDeploy && $hotspotListenIp === '') {
                    $hotspotDeployFinish(
                        'w',
                        'Hotspot non détecté sur le routeur. Utilisez « Send complet » pour la première installation (pool, profils, login.html).'
                    );
                }

                if ($useLightSend) {
                    $captiveApiUrl = rtrim($apiUrlForFetch, '/');
                    $dnsName = trim((string) ($config['hotspot_dns_name'] ?? ''));
                    $apiHostForDns = parse_url($apiUrlForFetch, PHP_URL_HOST);
                    $apiPort = (int) (parse_url($apiUrlForFetch, PHP_URL_PORT) ?: ((parse_url($apiUrlForFetch, PHP_URL_SCHEME) === 'https') ? 443 : 80));
                    if ($apiHostForDns && filter_var($apiHostForDns, FILTER_VALIDATE_IP)) {
                        $natResult = Mikrotik::ensureHotspotApiNatProxy($client, $hotspotListenIp, $apiHostForDns, $apiPort);
                        if (empty($natResult['ok']) || empty($natResult['captive_url'])) {
                            $hotspotDeployFinish(
                                'e',
                                'Proxy NAT hotspot API échoué ('
                                . $hotspotListenIp
                                . ':8080 → '
                                . $apiHostForDns
                                . ':'
                                . $apiPort
                                . ') : '
                                . implode(' | ', $natResult['errors'] ?? ['erreur inconnue'])
                                . '. Sans ce proxy, le paiement captif expire (timeout).'
                            );
                        }
                        $captiveApiUrl = $natResult['captive_url'];
                    } elseif (Mikrotik::isLocalHotspotDevEnvironment($apiUrlForFetch)) {
                        $captiveApiUrl = Mikrotik::resolveHotspotCaptiveProxyUrl($hotspotListenIp);
                        if ($captiveApiUrl === '') {
                            $hotspotDeployFinish(
                                'e',
                                'IP passerelle hotspot introuvable pour le proxy captif (port 8080).'
                            );
                        }
                    }
                    try {
                        $renderedLoginHtml = Mikrotik::patchHotspotLoginCaptiveApi(
                            $renderedLoginHtml,
                            $captiveApiUrl,
                            $dnsName,
                            $apiUrlForFetch
                        );
                        $uploadId = 'DYRSIA_UPLOAD_' . date('YmdHis', $fetchTs);
                        $renderedLoginHtml = preg_replace('/<!--\s*DYRSIA_UPLOAD_[^>]*-->\s*/', '', $renderedLoginHtml) ?? $renderedLoginHtml;
                        $renderedLoginHtml = preg_replace('/<meta\s+name=["\']dyrsia-generated-at["\'][^>]*>\s*/i', '', $renderedLoginHtml) ?? $renderedLoginHtml;
                        $renderedLoginHtml = str_replace(
                            '</head>',
                            '<meta name="dyrsia-generated-at" content="' . date('c', $fetchTs) . '">' . "\n"
                            . '<!-- ' . $uploadId . ' -->' . "\n"
                            . '</head>',
                            $renderedLoginHtml
                        );
                        file_put_contents($loginFilePath, $renderedLoginHtml);
                    } catch (Throwable $e) {
                        $hotspotDeployFinish( 'e', 'Erreur traitement HTML: ' . $e->getMessage());
                    }

                    $isLocalHotspotDev = Mikrotik::isLocalHotspotDevEnvironment($apiUrlForFetch);
                    $loginFetchUrls = $isLocalHotspotDev
                        ? []
                        : Mikrotik::buildHotspotLoginFetchUrls(
                            $apiUrlForFetch,
                            APP_URL,
                            $fetchTs,
                            array_filter([$captiveApiUrl]),
                            true,
                            $routerName
                        );

                    $profileReady = Mikrotik::ensureHotspotCaptiveProfileReady($client, $config, $hotspotServerName);
                    if (empty($profileReady['ok'])) {
                        $hotspotDeployFinish(
                            'e',
                            'Profil hotspot : ' . implode(' | ', $profileReady['errors'] ?? ['erreur inconnue'])
                        );
                    }

                    $deployResult = Mikrotik::deployHotspotLoginHtml($client, $renderedLoginHtml, $loginFetchUrls);
                    if (empty($deployResult['ok'])) {
                        $localDevHint = $isLocalHotspotDev
                            ? ' En local (localhost), seul l\'upload API MikroTik est utilisé — pas de /tool fetch. '
                            . 'Vérifiez WireGuard et les droits API (write + script). '
                            . 'Hotspot API URL joignable depuis le routeur : ex. http://10.0.0.2:8082.'
                            : '';
                        $hotspotDeployFinish(
                            'e',
                            'Échec envoi login.html : ' . implode(' | ', $deployResult['errors'] ?? ['échec inconnu']) . $localDevHint
                        );
                    }
                    $sentPath = (string) ($deployResult['path'] ?? 'hotspot/login.html');
                    $deployMethod = (string) ($deployResult['method'] ?? 'api');
                    $routerLoginSize = Mikrotik::getRouterFileSize($client, $sentPath);
                    if ($routerLoginSize <= 0) {
                        $hotspotDeployFinish(
                            'e',
                            'login.html absent sur le routeur après envoi. Essayez « Send complet » ou vérifiez /file print.'
                        );
                    }
                    $profileNote = !empty($profileReady['actions'])
                        ? ' ' . implode(', ', $profileReady['actions']) . '.'
                        : '';
                    $flashNote = '';
                    if (!empty($deployResult['flash_mirror']['ok'])) {
                        $flashNote = ' Copie flash/hotspot OK.';
                    } elseif (!empty($deployResult['flash_mirror']['error'])) {
                        $flashNote = ' (flash mirror: ' . $deployResult['flash_mirror']['error'] . ')';
                    }
                    $hotspotDeployFinish(
                        's',
                        'login.html envoyé vers « '
                        . $routerName
                        . ' » ('
                        . $deployMethod
                        . ', '
                        . $routerLoginSize
                        . ' octets).'
                        . $profileNote
                        . $flashNote
                        . ' ID: '
                        . $uploadId
                        . '.'
                    );
                }

                $hotspotSetupNote = '';
                @set_time_limit(600);
                // Hotspot déjà sur le routeur : ne pas refaire pool/bridge/firewall (5–10 min via VPN).
                // Send complet sur infra existante = réparation DHCP/firewall + login.html + forfaits (~1–3 min).
                $hotspotAlreadyDeployed = $hotspotListenIp !== '';
                $skipHotspotSetup = $hotspotAlreadyDeployed;
                $skipBridgeHardening = $hotspotAlreadyDeployed;
                if (!$skipHotspotSetup) {
                if (isset($hotspotDeployProgress) && is_callable($hotspotDeployProgress)) {
                    $hotspotDeployProgress('Configuration hotspot (pool, bridge, profil, serveur, DHCP)…');
                }
                $hotspotSetup = Mikrotik::applyHotspotSetupFromConfig(
                    $client,
                    $config,
                    is_array($mikrotik) ? $mikrotik : (method_exists($mikrotik, 'as_array') ? $mikrotik->as_array() : null),
                    $skipBridgeHardening
                );
                if (empty($hotspotSetup['ok'])) {
                    $setupErrors = $hotspotSetup['errors'] ?? ['erreur inconnue'];
                    $setupActions = $hotspotSetup['actions'] ?? [];
                    Mikrotik::refreshMikrotikClient(
                        $client,
                        is_array($mikrotik) ? $mikrotik : (method_exists($mikrotik, 'as_array') ? $mikrotik->as_array() : null),
                        45
                    );
                    sleep(2);
                    $repairIface = trim((string) ($hotspotSetup['interface'] ?? $config['hotspot_interface'] ?? 'bridge-hotspot'));
                    $repairLocal = trim((string) ($config['hotspot_local_address'] ?? ''));
                    $repairPool = trim((string) ($config['hotspot_pool_name'] ?? ''));
                    $repairHotspotName = trim((string) ($hotspotSetup['hotspot_name'] ?? $config['hotspot_name'] ?? ''));
                    $repair = Mikrotik::ensureHotspotInterceptIntegrity(
                        $client,
                        $repairIface,
                        $repairLocal,
                        $repairPool,
                        $repairHotspotName
                    );
                    $repairedListenIp = $repairHotspotName !== ''
                        ? Mikrotik::getHotspotServerAddress($client, $repairHotspotName)
                        : '';
                    if ($repairedListenIp === '' && $repairIface !== '') {
                        $repairedListenIp = Mikrotik::getHotspotServerAddress($client, '');
                    }
                    if (!empty($repair['ok']) && $repairedListenIp !== '') {
                        $hotspotListenIp = $repairedListenIp;
                        $hotspotServerName = $repairHotspotName !== '' ? $repairHotspotName : $hotspotServerName;
                        $hotspotSetupNote = ' Hotspot : setup partiel récupéré ('
                            . implode(', ', array_slice($setupActions, 0, 4))
                            . (count($setupActions) > 4 ? '…' : '')
                            . '). Réparation : '
                            . implode(', ', $repair['actions'] ?? [])
                            . '.';
                    } else {
                        $hotspotDeployFinish(
                            'e',
                            'Configuration hotspot MikroTik échouée : '
                            . implode(' | ', $setupErrors)
                            . ($setupActions !== []
                                ? ' [partiel : ' . implode(', ', array_slice($setupActions, 0, 6)) . ']'
                                : '')
                        );
                    }
                }
                if (!empty($hotspotSetup['radius_secret']) && trim((string) ($config['hotspot_radius_secret'] ?? '')) === '') {
                    $secretRow = ORM::for_table('tbl_appconfig')->where('setting', 'hotspot_radius_secret')->find_one();
                    if ($secretRow) {
                        $secretRow->value = $hotspotSetup['radius_secret'];
                        $secretRow->save();
                    } else {
                        $secretRow = ORM::for_table('tbl_appconfig')->create();
                        $secretRow->setting = 'hotspot_radius_secret';
                        $secretRow->value = $hotspotSetup['radius_secret'];
                        $secretRow->save();
                    }
                    $config['hotspot_radius_secret'] = $hotspotSetup['radius_secret'];
                }
                if (!empty($hotspotSetup['hotspot_name'])) {
                    $config['hotspot_name'] = $hotspotSetup['hotspot_name'];
                    $hotspotServerName = $hotspotSetup['hotspot_name'];
                }
                if (!empty($hotspotSetup['interface'])) {
                    $ifaceRow = ORM::for_table('tbl_appconfig')->where('setting', 'hotspot_interface')->find_one();
                    if ($ifaceRow) {
                        $ifaceRow->value = (string) $hotspotSetup['interface'];
                        $ifaceRow->save();
                    } else {
                        $ifaceRow = ORM::for_table('tbl_appconfig')->create();
                        $ifaceRow->setting = 'hotspot_interface';
                        $ifaceRow->value = (string) $hotspotSetup['interface'];
                        $ifaceRow->save();
                    }
                    $config['hotspot_interface'] = (string) $hotspotSetup['interface'];
                }
                if ($hotspotSetupNote === '' && !empty($hotspotSetup['actions'])) {
                    $hotspotSetupNote = ' Hotspot : ' . implode(', ', $hotspotSetup['actions']) . '.';
                }
                } else {
                    $dhcpIface = trim((string) ($config['hotspot_interface'] ?? ''));
                    if ($dhcpIface === '') {
                        $dhcpIface = Mikrotik::resolveSimpleHotspotInterface($client, $config);
                    }
                    $dhcpLocal = trim((string) ($config['hotspot_local_address'] ?? ''));
                    $dhcpPool = trim((string) ($config['hotspot_pool_name'] ?? ''));
                    $dhcpPoolRange = trim((string) ($config['hotspot_address_pool'] ?? $config['hotspot_pool_range'] ?? ''));
                    if ($dhcpLocal === '' && $dhcpPoolRange !== '') {
                        $derivedGw = Mikrotik::deriveGatewayFromPoolRange($dhcpPoolRange);
                        if ($derivedGw !== '') {
                            $dhcpLocal = $derivedGw . '/24';
                        }
                    }
                    $dhcpNote = '';
                    if ($dhcpIface !== '') {
                        // Bridge + ports même en mode incrémental (sans refaire tout le setup).
                        $bridgePrep = Mikrotik::ensureDedicatedHotspotBridge($client, $config);
                        if (!empty($bridgePrep['interface'])) {
                            $dhcpIface = (string) $bridgePrep['interface'];
                        }
                        if (!empty($bridgePrep['actions'])) {
                            $dhcpNote .= ' ' . implode(', ', $bridgePrep['actions']) . '.';
                        }
                        $dhcpResult = Mikrotik::ensureHotspotDhcpServer(
                            $client,
                            $dhcpIface,
                            $dhcpPool,
                            $dhcpLocal,
                            trim((string) ($config['hotspot_name'] ?? ''))
                        );
                        if (!empty($dhcpResult['actions'])) {
                            $dhcpNote .= ' ' . implode(', ', $dhcpResult['actions']) . '.';
                        }
                        if (!empty($dhcpResult['errors'])) {
                            $hotspotDeployFinish(
                                'e',
                                'DHCP hotspot échoué : ' . implode(' | ', $dhcpResult['errors'])
                            );
                        }
                        $dhcpFw = Mikrotik::ensureHotspotDhcpFirewallPass($client, $dhcpIface);
                        if (!empty($dhcpFw['actions'])) {
                            $dhcpNote .= ' ' . implode(', ', $dhcpFw['actions']) . '.';
                        }
                        $wgDhcp = Mikrotik::ensureHotspotWalledGardenDhcp($client);
                        if (!empty($wgDhcp['actions'])) {
                            $dhcpNote .= ' ' . implode(', ', $wgDhcp['actions']) . '.';
                        }
                    }
                    $hotspotSetupNote = ' Hotspot : infra déjà sur le routeur (login.html + forfaits + walled-garden).'
                        . $dhcpNote;
                }
                @set_time_limit(600);
                if (isset($hotspotDeployProgress) && is_callable($hotspotDeployProgress)) {
                    $hotspotDeployProgress('Synchronisation des forfaits…');
                }
                $planPush = $pushHotspotPlansToMikrotik($routerName, $client);
                if (empty($planPush['ok'])) {
                    $hotspotDeployFinish( 'e', $planPush['message'] ?? 'Synchronisation des forfaits Hotspot échouée.');
                }
                $planSyncResult = $planPush['result'] ?? [];
                $planSyncNote = '';
                if (!empty($planSyncResult['upserted']) || !empty($planSyncResult['removed'])) {
                    $planSyncNote = ' Forfaits : '
                        . (int) ($planSyncResult['upserted'] ?? 0)
                        . ' synchronisé(s), '
                        . (int) ($planSyncResult['removed'] ?? 0)
                        . ' ancien(s) supprimé(s).';
                }
                $fetchTs = time();
                $apiUrlForFetch = Mikrotik::resolveHotspotBackendApiUrl($config);
                if ($apiUrlForFetch === '') {
                    $hotspotDeployFinish( 'e', 'Hotspot API URL requise (ex. http://10.0.0.1 pour le VPS WireGuard, port 80).');
                }
                @set_time_limit(600);
                if (isset($hotspotDeployProgress) && is_callable($hotspotDeployProgress)) {
                    $hotspotDeployProgress('Walled-garden et proxy API captive…');
                }
                $wgResult = Mikrotik::ensureHotspotWalledGardenBatch($client, array_values(array_filter(array_unique([
                    $apiUrlForFetch,
                    'https://wifizones.org',
                    'https://www.wifizones.org',
                ]))));
                if (empty($wgResult['ok'])) {
                    $hotspotDeployFinish(
                        'e',
                        'Échec walled-garden : ' . implode(' | ', $wgResult['errors'] ?? ['erreur inconnue'])
                    );
                }
                $wgExtras = Mikrotik::ensureHotspotCaptiveExtrasWalledGarden($client, APP_URL, true);
                if (empty($wgExtras['ok'])) {
                    $hotspotDeployFinish(
                        'e',
                        'Walled-garden extras (CDN / site public) : ' . implode(' | ', $wgExtras['errors'] ?? ['erreur inconnue'])
                    );
                }
                $apiHostForDns = parse_url($apiUrlForFetch, PHP_URL_HOST);
                $dnsName = trim((string) ($config['hotspot_dns_name'] ?? ''));
                if ($hotspotServerName === '') {
                    $hotspotServerName = trim((string) ($config['hotspot_name'] ?? ''));
                }
                $hotspotListenIpForDns = Mikrotik::getHotspotServerAddress($client, $hotspotServerName);
                if ($hotspotListenIpForDns === '' && trim((string) ($config['hotspot_local_address'] ?? '')) !== '') {
                    $localAddrDns = trim((string) $config['hotspot_local_address']);
                    $hotspotListenIpForDns = strpos($localAddrDns, '/') !== false
                        ? explode('/', $localAddrDns, 2)[0]
                        : $localAddrDns;
                }
                // dns-name portail → passerelle hotspot (10.10.0.1), jamais l'IP API DYRSIA (10.0.0.2).
                if ($dnsName !== '' && $hotspotListenIpForDns !== '' && filter_var($hotspotListenIpForDns, FILTER_VALIDATE_IP)) {
                    $dnsResult = Mikrotik::ensureHotspotDnsStatic($client, $dnsName, $hotspotListenIpForDns);
                    if (empty($dnsResult['ok'])) {
                        $hotspotDeployFinish(
                            'e',
                            'DNS portail hotspot non configuré (' . $dnsName . ' → ' . $hotspotListenIpForDns . ') : '
                            . implode(' | ', $dnsResult['errors'] ?? ['erreur inconnue'])
                        );
                    }
                }
                $apiPort = (int) (parse_url($apiUrlForFetch, PHP_URL_PORT) ?: ((parse_url($apiUrlForFetch, PHP_URL_SCHEME) === 'https') ? 443 : 80));
                $captiveApiUrl = rtrim($apiUrlForFetch, '/');
                if ($hotspotServerName === '') {
                    $hotspotServerName = trim((string) ($config['hotspot_name'] ?? ''));
                }
                $hotspotListenIp = $hotspotListenIpForDns !== ''
                    ? $hotspotListenIpForDns
                    : Mikrotik::getHotspotServerAddress($client, $hotspotServerName);
                if ($hotspotListenIp === '') {
                    $hotspotDeployFinish(
                        'e',
                        'IP du serveur hotspot introuvable sur le MikroTik. Vérifiez /ip hotspot print (interface) '
                        . 'et /ip address print. Nom hotspot configuré : « '
                        . ($hotspotServerName !== '' ? $hotspotServerName : 'vide')
                        . ' » — doit correspondre à la colonne NAME dans /ip hotspot print.'
                    );
                }
                if ($apiHostForDns && filter_var($apiHostForDns, FILTER_VALIDATE_IP)) {
                    $natResult = Mikrotik::ensureHotspotApiNatProxy($client, $hotspotListenIp, $apiHostForDns, $apiPort);
                    if (empty($natResult['ok']) || empty($natResult['captive_url'])) {
                        $hotspotDeployFinish(
                            'e',
                            'Proxy NAT hotspot API échoué ('
                            . $hotspotListenIp
                            . ':8080 → '
                            . $apiHostForDns
                            . ':'
                            . $apiPort
                            . ') : '
                            . implode(' | ', $natResult['errors'] ?? ['erreur inconnue'])
                            . '. Sans ce proxy, le paiement captif expire (timeout).'
                        );
                    }
                    $captiveApiUrl = $natResult['captive_url'];
                } elseif (Mikrotik::isLocalHotspotDevEnvironment($apiUrlForFetch)) {
                    $captiveApiUrl = Mikrotik::resolveHotspotCaptiveProxyUrl($hotspotListenIp);
                    if ($captiveApiUrl === '') {
                        $hotspotDeployFinish(
                            'e',
                            'IP passerelle hotspot introuvable pour le proxy captif (port 8080).'
                        );
                    }
                } elseif (!filter_var($apiHostForDns, FILTER_VALIDATE_IP)) {
                    Mikrotik::ensureHotspotWalledGardenBatch($client, [
                        'https://wifizones.org',
                        'https://www.wifizones.org',
                    ]);
                }
                try {
                    $renderedLoginHtml = Mikrotik::patchHotspotLoginCaptiveApi(
                        $renderedLoginHtml,
                        $captiveApiUrl,
                        $dnsName,
                        $apiUrlForFetch
                    );
                    $uploadId = 'DYRSIA_UPLOAD_' . date('YmdHis', $fetchTs);
                    $renderedLoginHtml = preg_replace('/<!--\s*DYRSIA_UPLOAD_[^>]*-->\s*/', '', $renderedLoginHtml) ?? $renderedLoginHtml;
                    $renderedLoginHtml = preg_replace('/<meta\s+name=["\']dyrsia-generated-at["\'][^>]*>\s*/i', '', $renderedLoginHtml) ?? $renderedLoginHtml;
                    $renderedLoginHtml = str_replace('</head>', '<meta name="dyrsia-generated-at" content="' . date('c', $fetchTs) . '">' . "\n" . '<!-- ' . $uploadId . ' -->' . "\n" . '</head>', $renderedLoginHtml);
                    file_put_contents($loginFilePath, $renderedLoginHtml);
                } catch (Throwable $e) {
                    $hotspotDeployFinish( 'e', 'Erreur traitement HTML: ' . $e->getMessage());
                }
                $isLocalHotspotDev = Mikrotik::isLocalHotspotDevEnvironment($apiUrlForFetch);
                $loginFetchUrls = $isLocalHotspotDev
                    ? []
                    : Mikrotik::buildHotspotLoginFetchUrls(
                        $apiUrlForFetch,
                        APP_URL,
                        $fetchTs,
                        array_filter([$captiveApiUrl]),
                        true,
                        $routerName
                    );
                if (!$isLocalHotspotDev && empty($loginFetchUrls)) {
                    $hotspotDeployFinish(
                        'e',
                        'Hotspot API URL invalide pour le routeur. Utilisez l’IP LAN de ce Mac, ex. http://192.168.1.240:8080 (pas localhost).'
                    );
                }
                $fetchPreflight = $isLocalHotspotDev
                    ? true
                    : Mikrotik::verifyHotspotFetchUrls($loginFetchUrls, 2, $routerName);
                if (!$isLocalHotspotDev && $fetchPreflight !== true) {
                    if (is_file($loginFilePath) && filesize($loginFilePath) > 4000) {
                        // login.html est prêt localement ; le déploiement tente d'abord l'écriture API MikroTik.
                        $fetchPreflight = true;
                    }
                }
                if (!$isLocalHotspotDev && $fetchPreflight !== true) {
                    $preflightHint = '';
                    $apiHost = strtolower(trim((string) parse_url($apiUrlForFetch, PHP_URL_HOST)));
                    $apiPort = (int) (parse_url($apiUrlForFetch, PHP_URL_PORT) ?: ((parse_url($apiUrlForFetch, PHP_URL_SCHEME) === 'https') ? 443 : 80));
                    if ($apiHost !== '' && preg_match('/^10\.0\.0\./', $apiHost)) {
                        $preflightHint = ' Sur le VPS : nginx doit écouter sur '
                            . $apiHost
                            . ':80 et proxy vers l’app (voir scripts/nginx-wifizone-wireguard.conf.example).';
                    } elseif ($apiPort !== 80 && $apiPort !== 443) {
                        $preflightHint = ' Vérifiez que le serveur écoute sur 0.0.0.0:'
                            . $apiPort
                            . ' et que le pare-feu autorise ce port.';
                    }
                    $hotspotDeployFinish(
                        'e',
                        'Échec de l\'envoi vers MikroTik : ' . $fetchPreflight . $preflightHint
                    );
                }

                @set_time_limit(600);
                if (isset($hotspotDeployProgress) && is_callable($hotspotDeployProgress)) {
                    $hotspotDeployProgress('Envoi login.html vers le routeur…');
                }
                $profileReady = Mikrotik::ensureHotspotCaptiveProfileReady($client, $config, $hotspotServerName);
                if (empty($profileReady['ok'])) {
                    $hotspotDeployFinish(
                        'e',
                        'Profil hotspot : ' . implode(' | ', $profileReady['errors'] ?? ['erreur inconnue'])
                    );
                }
                $profileNote = !empty($profileReady['actions'])
                    ? ' ' . implode(', ', $profileReady['actions']) . '.'
                    : '';

                $deployResult = Mikrotik::deployHotspotLoginHtml($client, $renderedLoginHtml, $loginFetchUrls);
                if (empty($deployResult['ok'])) {
                    $sendErrors = $deployResult['errors'] ?? ['échec inconnu'];
                    $fetchTestUrl = !empty($loginFetchUrls)
                        ? $loginFetchUrls[0]
                        : (rtrim($apiUrlForFetch, '/') . '/system/uploads/mikrotik_hotspot/admin_'
                            . $hotspotAdminId . '/login.html?router=' . rawurlencode($routerName));
                    $fetchMode = (stripos($fetchTestUrl, 'https://') === 0) ? 'https' : 'http';
                    $localDevHint = $isLocalHotspotDev
                        ? ' En local (localhost), seul l\'upload API MikroTik est utilisé — pas de /tool fetch. '
                        . 'Vérifiez WireGuard, que nc -zv 10.0.0.5 8728 répond, et les droits API (write + script). '
                        . 'Hotspot API URL : http://10.0.0.2:8082 (joignable depuis le routeur).'
                        : ' Tests routeur : /tool fetch url="' . $fetchTestUrl . '" '
                        . 'dst-path=hotspot/login-test.html mode=' . $fetchMode
                        . ($fetchMode === 'https' ? ' check-certificate=no' : '');
                    $hotspotDeployFinish(
                        'e',
                        'Échec de l\'envoi vers MikroTik : login.html non envoyé (' . implode(' | ', $sendErrors)
                        . ').' . $localDevHint
                    );
                }
                $sentPath = (string) ($deployResult['path'] ?? 'hotspot/login.html');
                $deployMethod = (string) ($deployResult['method'] ?? 'api');
                $routerLoginSize = Mikrotik::getRouterFileSize($client, $sentPath);
                if ($routerLoginSize <= 0) {
                    $hotspotDeployFinish(
                        'e',
                        'Échec de l\'envoi vers MikroTik : ' . $sentPath . ' absent après envoi. Vérifiez /file print sur le routeur.'
                    );
                }

                $gatewayIp = '';
                $localAddr = trim((string) ($config['hotspot_local_address'] ?? ''));
                if ($localAddr !== '' && strpos($localAddr, '/') !== false) {
                    $gatewayIp = explode('/', $localAddr, 2)[0];
                }
                $bridgeForPortal = trim((string) ($config['hotspot_interface'] ?? 'bridge-hotspot'));
                $portalVerify = Mikrotik::verifyHotspotCaptivePortalReady(
                    $client,
                    $hotspotServerName,
                    $gatewayIp,
                    $bridgeForPortal
                );
                if (empty($portalVerify['ok'])) {
                    $hotspotDeployFinish(
                        'e',
                        'Portail captif incomplet : ' . implode(' | ', $portalVerify['errors'] ?? ['erreur inconnue'])
                    );
                }
                $portalNote = !empty($portalVerify['actions'])
                    ? ' Portail : ' . implode(', ', $portalVerify['actions']) . '.'
                    : '';

                $dnsNote = '';
                if ($dnsName !== '' && $hotspotListenIp !== '' && filter_var($hotspotListenIp, FILTER_VALIDATE_IP)) {
                    $dnsNote = ' DNS ' . $dnsName . ' → ' . $hotspotListenIp . '.';
                }
                $captiveNote = ($captiveApiUrl !== rtrim($apiUrlForFetch, '/'))
                    ? ' API captive : ' . $captiveApiUrl . ' (proxy NAT vers ' . $apiHostForDns . ').'
                    : '';
                $hotspotDeployFinish(
                    's',
                    $sendFullDeploy
                        ? 'Déploiement complet réussi'
                        : ('Envoi réussi vers MikroTik (' . $routerName . ') : pool, '
                            . $sentPath
                            . ' (' . $deployMethod . ', ' . $routerLoginSize . ' octets), walled-garden OK.'
                            . $hotspotSetupNote
                            . $profileNote
                            . $planSyncNote
                            . $portalNote
                            . ' ID: ' . $uploadId . '.'
                            . $dnsNote
                            . $captiveNote)
                );
