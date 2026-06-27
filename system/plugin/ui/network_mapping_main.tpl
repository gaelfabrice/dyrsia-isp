{include file="sections/header.tpl"}

<!-- Network Mapping Custom CSS -->
<link rel="stylesheet" href="system/plugin/ui/css/network_mapping.css" />

<!-- Leaflet CSS dan JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
            </div>
            <div class="panel-body">
                
                <!-- Statistik Jaringan -->
                <div class="stats-grid">
                    <div class="stat-card stat-card-router">
                        <div class="stat-card-icon">
                            <i class="fa fa-server"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3 class="stat-card-number">{$stats.total_routers}</h3>
                            <span class="stat-card-label">{$lang.total_routers}</span>
                            <div class="stat-card-status">
                                <span class="status-online"><i class="fa fa-circle"></i> <span id="countRouterAktif">0</span></span>
                                <span class="status-offline"><i class="fa fa-circle"></i> <span id="countRouterNonaktif">0</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-olt">
                        <div class="stat-card-icon">
                            <i class="fa fa-hdd-o"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3 class="stat-card-number">{$stats.total_olts}</h3>
                            <span class="stat-card-label">{$lang.total_olts}</span>
                            <div class="stat-card-status stat-card-status-olt">
                                <span class="status-online"><i class="fa fa-circle"></i> <span id="countOltActive">0</span></span>
                                <span class="status-maintenance"><i class="fa fa-circle"></i> <span id="countOltMaintenance">0</span></span>
                                <span class="status-offline"><i class="fa fa-circle"></i> <span id="countOltInactive">0</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-odc">
                        <div class="stat-card-icon">
                            <i class="fa fa-cube"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3 class="stat-card-number">{$stats.total_odcs}</h3>
                            <span class="stat-card-label">{$lang.total_odcs}</span>
                            <div class="stat-card-status stat-card-status-olt">
                                <span class="status-online"><i class="fa fa-circle"></i> <span id="countOdcActive">0</span></span>
                                <span class="status-maintenance"><i class="fa fa-circle"></i> <span id="countOdcMaintenance">0</span></span>
                                <span class="status-offline"><i class="fa fa-circle"></i> <span id="countOdcInactive">0</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-odp">
                        <div class="stat-card-icon">
                            <i class="fa fa-circle-o"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3 class="stat-card-number">{$stats.total_odps}</h3>
                            <span class="stat-card-label">{$lang.total_odps}</span>
                            <div class="stat-card-status stat-card-status-olt">
                                <span class="status-online"><i class="fa fa-circle"></i> <span id="countOdpActive">0</span></span>
                                <span class="status-maintenance"><i class="fa fa-circle"></i> <span id="countOdpMaintenance">0</span></span>
                                <span class="status-offline"><i class="fa fa-circle"></i> <span id="countOdpInactive">0</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-tiang">
                        <div class="stat-card-icon">
                            <i class="fa fa-ellipsis-v"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3 class="stat-card-number">{$stats.total_tiang}</h3>
                            <span class="stat-card-label">{$lang.total_tiang}</span>
                            <div class="stat-card-status stat-card-status-olt">
                                <span class="status-online"><i class="fa fa-circle"></i> <span id="countTiangBagus">0</span></span>
                                <span class="status-maintenance"><i class="fa fa-circle"></i> <span id="countTiangPerbaikan">0</span></span>
                                <span class="status-offline"><i class="fa fa-circle"></i> <span id="countTiangRusak">0</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-homepass">
                        <div class="stat-card-icon">
                            <i class="fa fa-home"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3 class="stat-card-number">{$stats.total_homepass}</h3>
                            <span class="stat-card-label">{$lang.total_homepass}</span>
                            <div class="stat-card-status stat-card-status-homepass">
                                <span class="status-hp-prospek"><i class="fa fa-circle"></i> <span id="countHpProspek">0</span></span>
                                <span class="status-hp-pending"><i class="fa fa-circle"></i> <span id="countHpPending">0</span></span>
                                <span class="status-hp-tidak-minat"><i class="fa fa-circle"></i> <span id="countHpTidakMinat">0</span></span>
                                <span class="status-hp-tidak-mampu"><i class="fa fa-circle"></i> <span id="countHpTidakMampu">0</span></span>
                                <span class="status-hp-rumah-kosong"><i class="fa fa-circle"></i> <span id="countHpRumahKosong">0</span></span>
                                <span class="status-hp-langganan"><i class="fa fa-circle"></i> <span id="countHpLangganan">0</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card stat-card-customer">
                        <div class="stat-card-icon">
                            <i class="fa fa-users"></i>
                        </div>
                        <div class="stat-card-info">
                            <h3 class="stat-card-number">{$stats.total_customers}</h3>
                            <span class="stat-card-label">{$lang.total_customers}</span>
                            <div class="stat-card-status stat-card-status-customer">
                                <span class="status-online"><i class="fa fa-circle"></i> <span id="countOnline">0</span></span>
                                <span class="status-isolir"><i class="fa fa-circle"></i> <span id="countIsolir">0</span></span>
                                <span class="status-offline"><i class="fa fa-circle"></i> <span id="countOffline">0</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Pencarian dengan Autocomplete -->
                <div class="search-autocomplete-wrapper" style="margin-bottom: 20px; position: relative;">
                    <div class="input-group">
                        <div class="input-group-addon">
                            <span class="fa fa-search"></span>
                        </div>
                        <input type="text" id="searchInput" class="form-control" 
                               placeholder="{$lang.search_placeholder}"
                               autocomplete="off"
                               onkeyup="onSearchInputKeyup(event)"
                               onfocus="onSearchInputFocus()"
                               onblur="onSearchInputBlur()">
                        <div class="input-group-btn">
                            <button class="btn btn-default" type="button" id="btnClearSearch" onclick="clearSearchInput()" style="display: none;">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Dropdown Hasil Pencarian -->
                    <div class="search-dropdown" id="searchDropdown">
                        <div class="search-dropdown-content" id="searchDropdownContent">
                            <!-- Hasil pencarian akan di-generate oleh JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Kontainer Peta -->
                <div id="map" class="well" style="width: 100%; height: 70vh; margin: 20px auto; position: relative;">
                    <div id="loading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000;">
                        <i class="fa fa-spinner fa-spin fa-3x"></i>
                        <br><br>{$lang.loading}
                    </div>
                    
                    <!-- Indikator Mode Pick -->
                    <div class="pick-mode-indicator" id="pickModeIndicator">
                        <i class="fa fa-crosshairs"></i>
                        <span id="pickModeText">{$lang.pick_location_olt}</span>
                        <button onclick="batalkanModePick()"><i class="fa fa-times"></i></button>
                    </div>
                    
                    <!-- Indikator Mode Kabel -->
                    <div class="kabel-mode-indicator" id="kabelModeIndicator" onclick="event.stopPropagation();" onmousedown="event.stopPropagation();">
                        <div class="kabel-mode-header">
                            <i class="fa fa-plug"></i>
                            <span id="kabelModeText">{$lang.cable_mode_start}</span>
                        </div>
                        <div class="kabel-mode-info" id="kabelModeInfo">
                            <span id="kabelCheckpointCount">{$lang.cable_checkpoint}: 0</span>
                            <span id="kabelDistanceCount">{$lang.cable_distance}: 0 m</span>
                        </div>
                        <div class="kabel-mode-buttons">
                            <button type="button" class="kabel-btn-undo" id="btnKabelUndo" onmousedown="event.stopPropagation();" onclick="event.stopPropagation(); undoKabelCheckpoint();" disabled>
                                <i class="fa fa-undo"></i> {$lang.btn_undo}
                            </button>
                            <button type="button" class="kabel-btn-cancel" onmousedown="event.stopPropagation();" onclick="event.stopPropagation(); batalkanModeKabel();">
                                <i class="fa fa-times"></i> {$lang.btn_cancel}
                            </button>
                        </div>
                    </div>
                    
                    <!-- Floating Button Switch Map Type -->
                    <div class="map-type-container">
                        <button class="map-type-fab" id="mapTypeFab" onclick="toggleMapTypeMenu()">
                            <i class="fa fa-th-large" id="mapTypeFabIcon"></i>
                        </button>
                        <div class="map-type-menu" id="mapTypeMenu">
                            <div class="map-type-menu-header">{$lang.map_type_header}</div>
                            <button class="map-type-menu-item active" id="btnPetaJalan" onclick="gantiModePeta('jalan')">
                                <div class="map-type-menu-icon">
                                    <i class="fa fa-road"></i>
                                </div>
                                <div class="map-type-menu-info">
                                    <span class="map-type-menu-title">{$lang.map_roadmap}</span>
                                    <span class="map-type-menu-desc">{$lang.map_roadmap_desc}</span>
                                </div>
                                <i class="fa fa-check map-type-check"></i>
                            </button>
                            <button class="map-type-menu-item" id="btnSatelit" onclick="gantiModePeta('satelit')">
                                <div class="map-type-menu-icon satelit">
                                    <i class="fa fa-globe"></i>
                                </div>
                                <div class="map-type-menu-info">
                                    <span class="map-type-menu-title">{$lang.map_satellite}</span>
                                    <span class="map-type-menu-desc">{$lang.map_satellite_desc}</span>
                                </div>
                                <i class="fa fa-check map-type-check"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Plugin Settings Button -->
                    <div class="plugin-settings-container">
                        <button class="plugin-settings-fab" id="pluginSettingsFab" onclick="togglePluginMenu()">
                            <i class="fa fa-cog" id="pluginSettingsIcon"></i>
                            <span class="plugin-update-badge" id="pluginUpdateBadge" style="display: none;"></span>
                        </button>
                        <div class="plugin-settings-menu" id="pluginSettingsMenu">
                            <div class="plugin-settings-header">
                                <i class="fa fa-puzzle-piece"></i> {$lang.plugin_settings}
                            </div>
                            <button class="plugin-settings-item" id="btn-check-update" onclick="checkForUpdate(true); togglePluginMenu();">
                                <div class="plugin-settings-icon update">
                                    <i class="fa fa-refresh"></i>
                                </div>
                                <div class="plugin-settings-info">
                                    <span class="plugin-settings-title">{$lang.check_update}</span>
                                    <span class="plugin-settings-desc" id="update-status">{$lang.click_to_check}</span>
                                </div>
                            </button>
                            <button class="plugin-settings-item uninstall" onclick="showUninstallModal(); togglePluginMenu();">
                                <div class="plugin-settings-icon uninstall">
                                    <i class="fa fa-trash"></i>
                                </div>
                                <div class="plugin-settings-info">
                                    <span class="plugin-settings-title">{$lang.uninstall}</span>
                                    <span class="plugin-settings-desc">{$lang.remove_plugin}</span>
                                </div>
                            </button>
                            <div class="plugin-settings-item language-container">
                                <div class="plugin-settings-icon language">
                                    <i class="fa fa-globe"></i>
                                </div>
                                <div class="plugin-settings-info">
                                    <span class="plugin-settings-title">{$lang.language}</span>
                                    <span class="plugin-settings-desc">{$lang.switch_language}</span>
                                </div>
                                <div class="language-buttons">
                                    <button class="lang-btn {if $current_lang == 'id'}active{/if}" onclick="switchLanguage('id'); togglePluginMenu();">
                                        ID
                                    </button>
                                    <button class="lang-btn {if $current_lang == 'en'}active{/if}" onclick="switchLanguage('en'); togglePluginMenu();">
                                        EN
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Floating Action Button -->
                    <div class="floating-container">
                        <!-- Tombol Utama -->
                        <button class="floating-btn floating-btn-main" id="fabMain" onclick="toggleFabMenu()">
                            <i class="fa fa-plus" id="fabIcon"></i>
                        </button>
                        
                        <!-- Menu Floating -->
                        <div class="floating-menu" id="fabMenu">
                            <!-- Grup Tambah Data -->
                            <div class="floating-menu-group">
                                <div class="floating-menu-header" onclick="toggleGrup('grupTambah')">
                                    <span class="floating-menu-label">{$lang.menu}</span>
                                    <i class="fa fa-chevron-down floating-menu-arrow" id="arrowGrupTambah"></i>
                                </div>
                                <div class="floating-menu-content" id="grupTambah">
                                    <div class="floating-btn-grid">
                                        <button class="floating-btn floating-btn-item fab-add-odc" onclick="bukaModalTambah('odc')" title="{$lang.modal_add_odc}">
                                            <i class="fa fa-cube"></i>
                                            <span>{$lang.odc}</span>
                                        </button>
                                        <button class="floating-btn floating-btn-item fab-add-odp" onclick="bukaModalTambah('odp')" title="{$lang.modal_add_odp}">
                                            <i class="fa fa-circle-o"></i>
                                            <span>{$lang.odp}</span>
                                        </button>
                                        <button class="floating-btn floating-btn-item fab-add-olt" onclick="bukaModalTambah('olt')" title="{$lang.modal_add_olt}">
                                            <i class="fa fa-hdd-o"></i>
                                            <span>{$lang.olt}</span>
                                        </button>
                                        <button class="floating-btn floating-btn-item fab-add-tiang" onclick="bukaModalTambah('tiang')" title="{$lang.modal_add_tiang}">
                                            <i class="fa fa-ellipsis-v"></i>
                                            <span>{$lang.tiang}</span>
                                        </button>
                                        <button class="floating-btn floating-btn-item fab-add-kabel" onclick="bukaModalTambah('kabel')" title="{$lang.modal_add_kabel}">
                                            <i class="fa fa-minus"></i>
                                            <span>{$lang.kabel}</span>
                                        </button>
                                        <button class="floating-btn floating-btn-item fab-add-homepass" onclick="bukaModalTambah('homepass')" title="{$lang.modal_add_homepass}">
                                            <i class="fa fa-home"></i>
                                            <span>{$lang.homepass}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Grup Lapisan -->
                            <div class="floating-menu-group">
                                <div class="floating-menu-header" onclick="toggleGrup('grupLapisan')">
                                    <span class="floating-menu-label">{$lang.layer}</span>
                                    <i class="fa fa-chevron-down floating-menu-arrow" id="arrowGrupLapisan"></i>
                                </div>
                                <div class="floating-menu-content" id="grupLapisan">
                                    <div class="floating-layer-controls">
                                        <label class="floating-layer-item">
                                            <input type="checkbox" id="show-routers" checked>
                                            <span class="layer-icon layer-router"><i class="fa fa-server"></i></span>
                                            <span class="layer-text">{$lang.router}</span>
                                        </label>
                                        <label class="floating-layer-item">
                                            <input type="checkbox" id="show-olts" checked>
                                            <span class="layer-icon layer-olt"><i class="fa fa-hdd-o"></i></span>
                                            <span class="layer-text">{$lang.olt}</span>
                                        </label>
                                        <label class="floating-layer-item">
                                            <input type="checkbox" id="show-odcs" checked>
                                            <span class="layer-icon layer-odc"><i class="fa fa-cube"></i></span>
                                            <span class="layer-text">{$lang.odc}</span>
                                        </label>
                                        <label class="floating-layer-item">
                                            <input type="checkbox" id="show-odps" checked>
                                            <span class="layer-icon layer-odp"><i class="fa fa-circle-o"></i></span>
                                            <span class="layer-text">{$lang.odp}</span>
                                        </label>
                                        <label class="floating-layer-item">
                                            <input type="checkbox" id="show-tiang" checked>
                                            <span class="layer-icon layer-tiang"><i class="fa fa-ellipsis-v"></i></span>
                                            <span class="layer-text">{$lang.tiang}</span>
                                        </label>
                                        <label class="floating-layer-item">
                                            <input type="checkbox" id="show-kabel" checked>
                                            <span class="layer-icon layer-kabel"><i class="fa fa-minus"></i></span>
                                            <span class="layer-text">{$lang.kabel}</span>
                                        </label>
                                        <label class="floating-layer-item">
                                            <input type="checkbox" id="show-homepass" checked>
                                            <span class="layer-icon layer-homepass"><i class="fa fa-home"></i></span>
                                            <span class="layer-text">{$lang.homepass}</span>
                                        </label>
                                        <label class="floating-layer-item">
                                            <input type="checkbox" id="show-customers" checked>
                                            <span class="layer-icon layer-customer"><i class="fa fa-users"></i></span>
                                            <span class="layer-text">{$lang.customer}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Modal Form OLT -->
                <div class="modal-overlay" id="modalOltOverlay">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4><i class="fa fa-hdd-o"></i> {$lang.modal_add_olt_title}</h4>
                            <button class="modal-close" onclick="tutupModalOlt()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="formOlt">
                                <input type="hidden" id="oltCoordinates" name="coordinates">
                                
                                <!-- Informasi Dasar -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-info-circle"></i>
                                        <span>{$lang.form_section_basic_info}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_olt_name_label} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="oltName" name="name" placeholder="{$lang.form_olt_name_placeholder}" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_brand_label}</label>
                                        <select class="form-control" id="oltBrand" name="brand">
                                            <option value="">{$lang.form_select_brand}</option>
                                            <option value="ZTE">ZTE</option>
                                            <option value="Huawei">Huawei</option>
                                            <option value="C-DATA">C-DATA</option>
                                            <option value="Nokia">Nokia</option>
                                            <option value="HSGQ">HSGQ</option>
                                            <option value="VSOL">VSOL</option>
                                            <option value="Lainnya">{$lang.form_brand_other}</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_model_label}</label>
                                        <input type="text" class="form-control" id="oltModel" name="model" placeholder="{$lang.form_model_placeholder}">
                                    </div>
                                </div>
                                
                                <!-- Koneksi Parent -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-plug"></i>
                                        <span>{$lang.form_section_parent_connection}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_connection_type_label}</label>
                                        <select class="form-control" id="oltParentType" name="parent_type" onchange="toggleParentSelect()">
                                            <option value="">{$lang.form_no_connection}</option>
                                            <option value="router">{$lang.form_router_mikrotik}</option>
                                            <option value="olt">{$lang.form_olt_other}</option>
                                        </select>
                                        <small class="form-help">{$lang.form_connection_help}</small>
                                    </div>
                                    
                                    <!-- Dropdown Parent Router -->
                                    <div class="form-group" id="groupParentRouter" style="display: none;">
                                        <label>{$lang.form_parent_router_label} <span class="text-danger">*</span></label>
                                        <select class="form-control" id="oltParentRouter" name="parent_router_id" onchange="handleParentChange()">
                                            <option value="">{$lang.form_select_router}</option>
                                            {foreach $all_routers as $router}
                                            <option value="{$router.id}">{$router.name}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                    
                                    <!-- Dropdown Parent OLT -->
                                    <div class="form-group" id="groupParentOlt" style="display: none;">
                                        <label>{$lang.form_parent_olt_label} <span class="text-danger">*</span></label>
                                        <select class="form-control" id="oltParentOlt" name="parent_olt_id" onchange="handleParentChange()">
                                            <option value="">{$lang.form_select_olt}</option>
                                            {foreach $all_olts as $olt_item}
                                            <option value="{$olt_item.id}">{$olt_item.name} ({$lang.form_ports_available|replace:'{available}':($olt_item.total_ports - $olt_item.used_ports)})</option>
                                            {/foreach}
                                        </select>
                                        <small class="form-help" id="helpNoOlt" style="display: none; color: #ef4444;">
                                            <i class="fa fa-info-circle"></i> {$lang.form_no_olt_available}
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Konfigurasi Port -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-sitemap"></i>
                                        <span>{$lang.form_section_port_config}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_total_ports_label}</label>
                                        <div class="port-input-wrapper">
                                            <button type="button" class="port-btn port-btn-minus" onclick="ubahPortAdd('total', -1)">
                                                <i class="fa fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control port-number" id="oltTotalPorts" name="total_ports" value="8" min="1" max="128" readonly>
                                            <button type="button" class="port-btn port-btn-plus" onclick="ubahPortAdd('total', 1)">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_used_ports_label}</label>
                                        <div class="port-input-wrapper">
                                            <button type="button" class="port-btn port-btn-minus" onclick="ubahPortAdd('used', -1)">
                                                <i class="fa fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control port-number" id="oltUsedPorts" name="used_ports" value="0" min="0" max="128" readonly>
                                            <button type="button" class="port-btn port-btn-plus" onclick="ubahPortAdd('used', 1)">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.form_used_ports_help}</small>
                                    </div>
                                    
                                    <div class="port-info-simple">
                                        {$lang.form_ports_available_simple}<strong id="portTersedia">8</strong>
                                    </div>
                                    
                                    <!-- Container untuk Multi-Port Fields (Add OLT) -->
                                    <div id="addPortsContainer" style="margin-top: 15px;">
                                        <!-- Fields PON akan di-generate secara dinamis oleh JavaScript -->
                                    </div>
                                </div>
                                
                                <!-- Identitas Perangkat -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-barcode"></i>
                                        <span>{$lang.form_section_device_identity}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_serial_number_label}</label>
                                        <input type="text" class="form-control" id="oltSerialNumber" name="serial_number" placeholder="{$lang.form_serial_number_placeholder}">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_ip_address_label}</label>
                                        <input type="text" class="form-control" id="oltIpAddress" name="ip_address" placeholder="{$lang.form_ip_address_placeholder}">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_operational_status_label}</label>
                                        <select class="form-control" id="oltStatus" name="status">
                                            <option value="Active">{$lang.form_status_online}</option>
                                            <option value="Inactive">{$lang.form_status_offline}</option>
                                            <option value="Maintenance">{$lang.form_status_maintenance}</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Lokasi & Keterangan -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-map-marker"></i>
                                        <span>{$lang.form_section_location}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_map_coordinates_label} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="oltCoordinatesDisplay" readonly placeholder="{$lang.form_coordinates_placeholder}">
                                        <small class="form-help">{$lang.form_coordinates_help}</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_location_address_label}</label>
                                        <textarea class="form-control" id="oltAddress" name="address" rows="2" placeholder="{$lang.form_address_placeholder}"></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_additional_notes_label}</label>
                                        <textarea class="form-control" id="oltDescription" name="description" rows="2" placeholder="{$lang.form_notes_placeholder}"></textarea>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="tutupModalOlt()">
                                <i class="fa fa-times"></i> {$lang.btn_cancel}
                            </button>
                            <button type="button" class="btn btn-primary" onclick="simpanOlt()">
                                <i class="fa fa-save"></i> {$lang.btn_save_olt}
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Modal Form ODC -->
                <div class="modal-overlay" id="modalOdcOverlay">
                    <div class="modal-content">
                        <div class="modal-header modal-header-odc">
                            <h4><i class="fa fa-cube"></i> <span id="modalOdcTitle">{$lang.modal_add_odc_title}</span></h4>
                            <button class="modal-close" onclick="tutupModalOdc()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="formOdc">
                                <input type="hidden" id="odcCoordinates" name="coordinates">
                                <input type="hidden" id="odcBoxId" name="box_id">
                                <input type="hidden" id="odcIsNewBox" name="is_new_box" value="yes">
                                
                                <!-- Pilihan Mode: Box Baru atau Tambah Slot -->
                                <div class="form-section" id="sectionBoxMode">
                                    <div class="form-section-header">
                                        <i class="fa fa-th-large"></i>
                                        <span>{$lang.addition_mode}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="box-mode-options">
                                            <label class="box-mode-option active" id="optionNewBox" onclick="setOdcBoxMode('new')">
                                                <input type="radio" name="box_mode" value="new" checked>
                                                <i class="fa fa-plus-square"></i>
                                                <span>{$lang.odc_box_new}</span>
                                                <small>{$lang.odc_box_new_desc}</small>
                                            </label>
                                            <label class="box-mode-option" id="optionAddSlot" onclick="setOdcBoxMode('existing')">
                                                <input type="radio" name="box_mode" value="existing">
                                                <i class="fa fa-clone"></i>
                                                <span>{$lang.odc_box_add_slot}</span>
                                                <small>{$lang.odc_box_add_slot_desc}</small>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Pilih Box Existing (hanya tampil jika mode 'existing') -->
                                    <div class="form-group" id="odcGroupSelectBox" style="display: none;">
                                        <label>{$lang.odc_select_box_label} <span class="text-danger">*</span></label>
                                        <select class="form-control" id="odcSelectBox" onchange="onSelectExistingBox()">
                                            <option value="">-- {$lang.odc_select_box_option} --</option>
                                        </select>
                                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.odc_select_box_help}</small>
                                    </div>
                                </div>
                                
                                <!-- Informasi Dasar -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-info-circle"></i>
                                        <span>{$lang.section_basic_info}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.odc_name_slot_label} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="odcName" name="name" placeholder="{$lang.odc_name_slot_placeholder}" required>
                                        <small class="form-help" id="odcNameHelp">{$lang.unique_name_odc}</small>
                                    </div>
                                </div>
                                
                                <!-- Koneksi Parent -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-plug"></i>
                                        <span>{$lang.section_parent_connection}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.connection_type_label}</label>
                                        <select class="form-control" id="odcParentType" name="parent_type" onchange="toggleOdcParentSelect()">
                                            <option value="">-- {$lang.option_no_connection} --</option>
                                            <option value="olt">{$lang.option_olt}</option>
                                            <option value="odc">{$lang.option_other_odc}</option>
                                        </select>
                                        <small class="form-help">{$lang.connection_type_help}</small>
                                    </div>
                                    
                                    <!-- Dropdown Parent OLT -->
                                    <div class="form-group" id="odcGroupParentOlt" style="display: none;">
                                        <label>{$lang.form_parent_olt} <span class="text-danger">*</span></label>
                                        <select class="form-control" id="odcParentOlt" name="parent_olt_id">
                                            <option value="">-- {$lang.option_select_olt} --</option>
                                            {foreach $all_olts as $olt_item}
                                            <option value="{$olt_item.id}">{$olt_item.name}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                    
                                    <!-- Dropdown Parent ODC -->
                                    <div class="form-group" id="odcGroupParentOdc" style="display: none;">
                                        <label>{$lang.form_parent_odc_slot} <span class="text-danger">*</span></label>
                                        <select class="form-control" id="odcParentOdc" name="parent_odc_id">
                                            <option value="">-- {$lang.option_select_odc_slot} --</option>
                                            {foreach $all_odcs as $odc_item}
                                            <option value="{$odc_item.id}">{$odc_item.name} ({$lang.text_port}: {$odc_item.total_ports - $odc_item.used_ports} {$lang.text_available})</option>
                                            {/foreach}
                                        </select>
                                        <small class="form-help" id="odcHelpNoOdc" style="display: none; color: #ef4444;">
                                            <i class="fa fa-info-circle"></i> {$lang.odc_no_other_available}
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Konfigurasi Port -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-sitemap"></i>
                                        <span>{$lang.form_port_configuration}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_total_ports}</label>
                                        <div class="port-input-wrapper">
                                            <button type="button" class="port-btn port-btn-minus" onclick="ubahOdcPortAdd('total', -1)">
                                                <i class="fa fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control port-number" id="odcTotalPorts" name="total_ports" value="8" min="1" max="128" readonly>
                                            <button type="button" class="port-btn port-btn-plus" onclick="ubahOdcPortAdd('total', 1)">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_used_ports}</label>
                                        <div class="port-input-wrapper">
                                            <button type="button" class="port-btn port-btn-minus" onclick="ubahOdcPortAdd('used', -1)">
                                                <i class="fa fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control port-number" id="odcUsedPorts" name="used_ports" value="0" min="0" max="128" readonly>
                                            <button type="button" class="port-btn port-btn-plus" onclick="ubahOdcPortAdd('used', 1)">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.odc_used_ports_help}</small>
                                    </div>
                                    
                                    <div class="port-info-simple">
                                        {$lang.form_available_ports}: <strong id="odcPortTersedia">8</strong>
                                    </div>
                                    
                                    <!-- Container untuk Multi-Port Fields (Add ODC) -->
                                    <div id="addOdcPortsContainer" style="margin-top: 15px;">
                                        <!-- {$lang.odc_port_fields_generated} -->
                                    </div>
                                </div>
                                
                                <!-- Status Operasional -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-toggle-on"></i>
                                        <span>{$lang.section_operational_status}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_status}</label>
                                        <select class="form-control" id="odcStatus" name="status">
                                            <option value="Active">{$lang.status_active}</option>
                                            <option value="Inactive">{$lang.status_inactive}</option>
                                            <option value="Maintenance">{$lang.status_maintenance}</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Lokasi & Keterangan -->
                                <div class="form-section" id="sectionOdcLokasi">
                                    <div class="form-section-header">
                                        <i class="fa fa-map-marker"></i>
                                        <span>{$lang.section_location_notes}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_map_coordinates} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="odcCoordinatesDisplay" readonly placeholder="{$lang.placeholder_auto_fill_map}">
                                        <small class="form-help">{$lang.coordinates_selected_help}</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.location_address_label}</label>
                                        <textarea class="form-control" id="odcAddress" name="address" rows="2" placeholder="{$lang.placeholder_address_example}"></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.additional_notes_label}</label>
                                        <textarea class="form-control" id="odcDescription" name="description" rows="2" placeholder="{$lang.placeholder_notes_optional}"></textarea>
                                    </div>
                                </div>
                                
                                <!-- Section Foto ODC (hanya untuk Box Baru / Slot Pertama) -->
                                <div class="form-section" id="sectionAddOdcFoto">
                                    <div class="form-section-header">
                                        <i class="fa fa-camera"></i>
                                        <span>{$lang.section_odc_photo}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.upload_photo_label} <small class="text-muted">({$lang.photo_max_size_format})</small></label>
                                        <div class="foto-upload-container">
                                            <input type="file" id="addOdcFoto" name="foto_odc" accept="image/jpeg,image/jpg,image/png,image/webp" onchange="previewFotoOdcAdd(this)" style="display: none;">
                                            <div class="foto-preview-wrapper" id="addOdcFotoPreviewWrapper">
                                                <div class="foto-placeholder" id="addOdcFotoPlaceholder" onclick="document.getElementById('addOdcFoto').click()">
                                                    <i class="fa fa-camera"></i>
                                                    <span>{$lang.click_to_upload_photo}</span>
                                                </div>
                                                <div class="foto-preview" id="addOdcFotoPreview" style="display: none;">
                                                    <img id="addOdcFotoPreviewImg" src="" alt="Preview">
                                                    <div class="foto-preview-actions">
                                                        <button type="button" class="btn-foto-change" onclick="document.getElementById('addOdcFoto').click()" title="{$lang.change_photo_title}">
                                                            <i class="fa fa-refresh"></i>
                                                        </button>
                                                        <button type="button" class="btn-foto-remove" onclick="hapusFotoOdcAddPreview()" title="{$lang.delete_photo_title}">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.photo_auto_resize_help}</small>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="tutupModalOdc()">
                                <i class="fa fa-times"></i> {$lang.btn_cancel}
                            </button>
                            <button type="button" class="btn btn-success" onclick="simpanOdc()">
                                <i class="fa fa-save"></i> {$lang.btn_save} {$lang.odc}
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Modal Form ODP -->
                <div class="modal-overlay" id="modalOdpOverlay">
                    <div class="modal-content">
                        <div class="modal-header modal-header-odp">
                            <h4><i class="fa fa-circle-o"></i> <span id="modalOdpTitle">{$lang.modal_add_odp}</span></h4>
                            <button class="modal-close" onclick="tutupModalOdp()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="formOdp">
                                <input type="hidden" id="odpCoordinates" name="coordinates">
                                <input type="hidden" id="odpBoxId" name="box_id">
                                <input type="hidden" id="odpIsNewBox" name="is_new_box" value="yes">
                                
                                <!-- Pilihan Mode: Box Baru atau Tambah Slot -->
                                <div class="form-section" id="sectionOdpBoxMode">
                                    <div class="form-section-header">
                                        <i class="fa fa-th-large"></i>
                                        <span>{$lang.addition_mode}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="box-mode-options box-mode-options-odp">
                                            <label class="box-mode-option box-mode-option-odp active" id="optionOdpNewBox" onclick="setOdpBoxMode('new')">
                                                <input type="radio" name="odp_box_mode" value="new" checked>
                                                <i class="fa fa-plus-square"></i>
                                                <span>{$lang.odc_box_new}</span>
                                                <small>{$lang.odp_box_new_desc}</small>
                                            </label>
                                            <label class="box-mode-option box-mode-option-odp" id="optionOdpAddSlot" onclick="setOdpBoxMode('existing')">
                                                <input type="radio" name="odp_box_mode" value="existing">
                                                <i class="fa fa-clone"></i>
                                                <span>{$lang.odc_box_add_slot}</span>
                                                <small>{$lang.odp_box_add_slot_desc}</small>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Pilih Box Existing -->
                                    <div class="form-group" id="odpGroupSelectBox" style="display: none;">
                                        <label>{$lang.odp_select_box_label} <span class="text-danger">*</span></label>
                                        <select class="form-control" id="odpSelectBox" onchange="onSelectExistingOdpBox()">
                                            <option value="">-- {$lang.odp_select_box_option} --</option>
                                        </select>
                                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.odp_select_box_help}</small>
                                    </div>
                                </div>
                                
                                <!-- Informasi Dasar -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-info-circle"></i>
                                        <span>{$lang.section_basic_info}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.odp_name_slot_label} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="odpName" name="name" placeholder="{$lang.odp_name_slot_placeholder}" required>
                                        <small class="form-help" id="odpNameHelp">{$lang.unique_name_odp}</small>
                                    </div>
                                </div>
                                
                                <!-- Koneksi Parent -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-plug"></i>
                                        <span>{$lang.section_parent_connection}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.connection_type_label}</label>
                                        <select class="form-control" id="odpParentType" name="parent_type" onchange="toggleOdpParentSelect()">
                                            <option value="">-- {$lang.option_no_connection} --</option>
                                            <option value="odc">{$lang.option_odc_slot}</option>
                                            <option value="odp">{$lang.option_other_odp}</option>
                                        </select>
                                        <small class="form-help">{$lang.odp_connection_type_help}</small>
                                    </div>
                                    
                                    <!-- Dropdown Parent ODC -->
                                    <div class="form-group" id="odpGroupParentOdc" style="display: none;">
                                        <label>{$lang.form_parent_odc_slot} <span class="text-danger">*</span></label>
                                        <select class="form-control" id="odpParentOdc" name="parent_odc_id">
                                            <option value="">-- {$lang.option_select_odc_slot} --</option>
                                            {foreach $all_odcs as $odc_item}
                                            <option value="{$odc_item.id}">{$odc_item.name} ({$lang.text_port}: {$odc_item.total_ports - $odc_item.used_ports} {$lang.text_available})</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                    
                                    <!-- Dropdown Parent ODP -->
                                    <div class="form-group" id="odpGroupParentOdp" style="display: none;">
                                        <label>{$lang.form_parent_odp_slot} <span class="text-danger">*</span></label>
                                        <select class="form-control" id="odpParentOdp" name="parent_odp_id">
                                            <option value="">-- {$lang.option_select_odp_slot} --</option>
                                            {foreach $all_odps as $odp_item}
                                            <option value="{$odp_item.id}">{$odp_item.name} ({$lang.text_port}: {$odp_item.total_ports - $odp_item.used_ports} {$lang.text_available})</option>
                                            {/foreach}
                                        </select>
                                        <small class="form-help" id="odpHelpNoOdp" style="display: none; color: #ef4444;">
                                            <i class="fa fa-info-circle"></i> {$lang.odp_no_other_available}
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Konfigurasi Port -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-sitemap"></i>
                                        <span>{$lang.form_port_configuration}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_total_ports}</label>
                                        <div class="port-input-wrapper">
                                            <button type="button" class="port-btn port-btn-minus" onclick="ubahOdpPortAdd('total', -1)">
                                                <i class="fa fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control port-number" id="odpTotalPorts" name="total_ports" value="8" min="1" max="128" readonly>
                                            <button type="button" class="port-btn port-btn-plus" onclick="ubahOdpPortAdd('total', 1)">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_used_ports}</label>
                                        <div class="port-input-wrapper">
                                            <button type="button" class="port-btn port-btn-minus" onclick="ubahOdpPortAdd('used', -1)">
                                                <i class="fa fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control port-number" id="odpUsedPorts" name="used_ports" value="0" min="0" max="128" readonly>
                                            <button type="button" class="port-btn port-btn-plus" onclick="ubahOdpPortAdd('used', 1)">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.odc_used_ports_help}</small>
                                    </div>
                                    
                                    <div class="port-info-simple">
                                        {$lang.form_available_ports}: <strong id="odpPortTersedia">8</strong>
                                    </div>
                                    
                                    <!-- Container untuk Multi-Port Fields (Add ODP) -->
                                    <div id="addOdpPortsContainer" style="margin-top: 15px;">
                                        <!-- {$lang.odp_port_fields_generated} -->
                                    </div>
                                </div>
                                
                                <!-- Status Operasional -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-toggle-on"></i>
                                        <span>{$lang.section_operational_status}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_status}</label>
                                        <select class="form-control" id="odpStatus" name="status">
                                            <option value="Active">{$lang.status_active}</option>
                                            <option value="Inactive">{$lang.status_inactive}</option>
                                            <option value="Maintenance">{$lang.status_maintenance}</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Lokasi & Keterangan -->
                                <div class="form-section" id="sectionOdpLokasi">
                                    <div class="form-section-header">
                                        <i class="fa fa-map-marker"></i>
                                        <span>{$lang.section_location_notes}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_map_coordinates} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="odpCoordinatesDisplay" readonly placeholder="{$lang.placeholder_auto_fill_map}">
                                        <small class="form-help">{$lang.odp_coordinates_selected_help}</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.location_address_label}</label>
                                        <textarea class="form-control" id="odpAddress" name="address" rows="2" placeholder="{$lang.placeholder_address_example}"></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.additional_notes_label}</label>
                                        <textarea class="form-control" id="odpDescription" name="description" rows="2" placeholder="{$lang.placeholder_notes_optional}"></textarea>
                                    </div>
                                </div>
                                
                                <!-- Section Foto ODP (hanya untuk Box Baru / Slot Pertama) -->
                                <div class="form-section" id="sectionAddOdpFoto">
                                    <div class="form-section-header">
                                        <i class="fa fa-camera"></i>
                                        <span>{$lang.section_odp_photo}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.upload_photo_label} <small class="text-muted">({$lang.photo_max_size_format})</small></label>
                                        <div class="foto-upload-container">
                                            <input type="file" id="addOdpFoto" name="foto_odp" accept="image/jpeg,image/jpg,image/png,image/webp" onchange="previewFotoOdpAdd(this)" style="display: none;">
                                            <div class="foto-preview-wrapper" id="addOdpFotoPreviewWrapper">
                                                <div class="foto-placeholder" id="addOdpFotoPlaceholder" onclick="document.getElementById('addOdpFoto').click()">
                                                    <i class="fa fa-camera"></i>
                                                    <span>{$lang.click_to_upload_photo}</span>
                                                </div>
                                                <div class="foto-preview" id="addOdpFotoPreview" style="display: none;">
                                                    <img id="addOdpFotoPreviewImg" src="" alt="Preview">
                                                    <div class="foto-preview-actions">
                                                        <button type="button" class="btn-foto-change" onclick="document.getElementById('addOdpFoto').click()" title="{$lang.change_photo_title}">
                                                            <i class="fa fa-refresh"></i>
                                                        </button>
                                                        <button type="button" class="btn-foto-remove" onclick="hapusFotoOdpAddPreview()" title="{$lang.delete_photo_title}">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.photo_auto_resize_help}</small>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="tutupModalOdp()">
                                <i class="fa fa-times"></i> {$lang.btn_cancel}
                            </button>
                            <button type="button" class="btn btn-warning" onclick="simpanOdp()">
                                <i class="fa fa-save"></i> {$lang.btn_save} {$lang.odp}
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Modal Form Tiang -->
                <div class="modal-overlay" id="modalTiangOverlay">
                    <div class="modal-content">
                        <div class="modal-header modal-header-tiang">
                            <h4><i class="fa fa-minus"></i> {$lang.modal_add_tiang}</h4>
                            <button class="modal-close" onclick="tutupModalTiang()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="formTiang">
                                <input type="hidden" id="tiangCoordinates" name="coordinates">
                                
                                <!-- Informasi Dasar -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-info-circle"></i>
                                        <span>{$lang.section_basic_info}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_id_name} {$lang.tiang} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="tiangName" name="name" placeholder="{$lang.tiang_name_placeholder}" required>
                                    </div>
                                </div>
                                
                                <!-- Informasi Tiang -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-arrows-v"></i>
                                        <span>{$lang.section_pole_info}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.pole_length_label}</label>
                                        <input type="number" class="form-control" id="tiangPanjang" name="panjang" placeholder="{$lang.pole_length_placeholder}" step="0.1" min="0">
                                        <small class="form-help">{$lang.pole_length_help}</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.pole_material_label}</label>
                                        <select class="form-control" id="tiangBahan" name="bahan">
                                            <option value="">-- {$lang.pole_material_option} --</option>
                                            <option value="Besi">{$lang.material_steel}</option>
                                            <option value="Beton">{$lang.material_concrete}</option>
                                            <option value="Kayu">{$lang.material_wood}</option>
                                            <option value="Galvanis">{$lang.material_galvanized}</option>
                                            <option value="Komposit">{$lang.material_composite}</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_slack_cable} ({$lang.unit_meter})</label>
                                        <input type="number" class="form-control" id="tiangSlackKabel" name="slack_kabel" placeholder="{$lang.slack_cable_placeholder}" step="0.1" min="0">
                                        <small class="form-help">{$lang.slack_cable_help}</small>
                                    </div>
                                </div>
                                
                                <!-- Status Operasional -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-toggle-on"></i>
                                        <span>{$lang.section_operational_status}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_status}</label>
                                        <select class="form-control" id="tiangStatus" name="status">
                                            <option value="Aktif">{$lang.status_active}</option>
                                            <option value="Perbaikan">{$lang.status_repair}</option>
                                            <option value="Rusak">{$lang.status_damaged}</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Lokasi & Keterangan -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-map-marker"></i>
                                        <span>{$lang.section_location_notes}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.form_map_coordinates} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="tiangCoordinatesDisplay" readonly placeholder="{$lang.placeholder_auto_fill_map}">
                                        <small class="form-help">{$lang.pole_coordinates_selected_help}</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.location_address_label}</label>
                                        <textarea class="form-control" id="tiangAddress" name="address" rows="2" placeholder="{$lang.placeholder_address_example}"></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.additional_notes_label}</label>
                                        <textarea class="form-control" id="tiangDescription" name="description" rows="2" placeholder="{$lang.placeholder_notes_optional}"></textarea>
                                    </div>
                                </div>
                                
                                <!-- Section Foto Tiang -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <i class="fa fa-camera"></i>
                                        <span>{$lang.section_pole_photo}</span>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>{$lang.upload_photo_label} <small class="text-muted">({$lang.photo_max_size_format})</small></label>
                                        <div class="foto-upload-container">
                                            <input type="file" id="tiangFoto" name="foto_tiang" accept="image/jpeg,image/jpg,image/png,image/webp" onchange="previewFotoTiang(this)" style="display: none;">
                                            <div class="foto-preview-wrapper" id="tiangFotoPreviewWrapper">
                                                <div class="foto-placeholder" id="tiangFotoPlaceholder" onclick="document.getElementById('tiangFoto').click()">
                                                    <i class="fa fa-camera"></i>
                                                    <span>{$lang.click_to_upload_photo}</span>
                                                </div>
                                                <div class="foto-preview" id="tiangFotoPreview" style="display: none;">
                                                    <img id="tiangFotoPreviewImg" src="" alt="Preview">
                                                    <div class="foto-preview-actions">
                                                        <button type="button" class="btn-foto-change" onclick="document.getElementById('tiangFoto').click()" title="{$lang.change_photo_title}">
                                                            <i class="fa fa-refresh"></i>
                                                        </button>
                                                        <button type="button" class="btn-foto-remove" onclick="hapusFotoTiangPreview()" title="{$lang.delete_photo_title}">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.photo_auto_resize_help}</small>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="tutupModalTiang()">
                                <i class="fa fa-times"></i> {$lang.btn_cancel}
                            </button>
                            <button type="button" class="btn btn-tiang" onclick="simpanTiang()">
                                <i class="fa fa-save"></i> {$lang.btn_save} {$lang.tiang}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Form Kabel -->
    <div class="modal-overlay" id="modalKabelOverlay">
        <div class="modal-content">
            <div class="modal-header modal-header-kabel">
                <h4><i class="fa fa-minus"></i> {$lang.modal_save_cable}</h4>
                <button class="modal-close" onclick="tutupModalKabel()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formKabel">
                    <input type="hidden" id="kabelDevice1Type" name="device_1_type">
                    <input type="hidden" id="kabelDevice1Id" name="device_1_id">
                    <input type="hidden" id="kabelDevice2Type" name="device_2_type">
                    <input type="hidden" id="kabelDevice2Id" name="device_2_id">
                    <input type="hidden" id="kabelCoordinatesPath" name="coordinates_path">
                    
                    <!-- Informasi Koneksi -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa fa-plug"></i>
                            <span>{$lang.section_connection_info}</span>
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.cable_name_label}</label>
                            <input type="text" class="form-control" id="kabelName" name="name" placeholder="{$lang.cable_name_placeholder}">
                            <small class="form-help">{$lang.cable_name_help}</small>
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.form_from_device}</label>
                            <input type="text" class="form-control" id="kabelDariDisplay" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.form_to_device}</label>
                            <input type="text" class="form-control" id="kabelKeDisplay" readonly>
                        </div>
                    </div>
                    
                    <!-- Informasi Kabel -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa fa-arrows-h"></i>
                            <span>{$lang.section_cable_info}</span>
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.form_cable_length} ({$lang.unit_meter})</label>
                            <input type="number" class="form-control" id="kabelPanjang" name="panjang" step="0.1" min="0" placeholder="{$lang.cable_length_placeholder}">
                            <small class="form-help">{$lang.cable_length_help}</small>
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.form_description}</label>
                            <textarea class="form-control" id="kabelDescription" name="description" rows="2" placeholder="{$lang.placeholder_notes_optional}"></textarea>
                        </div>
                    </div>
                    
                    <!-- Sambungan Kabel -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa fa-link"></i>
                            <span>{$lang.section_cable_connection}</span>
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.cable_connection_count_label}</label>
                            <div class="port-input-wrapper">
                                <button type="button" class="port-btn port-btn-minus" onclick="ubahJumlahSambungan(-1)">
                                    <i class="fa fa-minus"></i>
                                </button>
                                <input type="number" class="form-control port-number" id="kabelJumlahSambungan" name="jumlah_sambungan" value="0" min="0" max="20" readonly>
                                <button type="button" class="port-btn port-btn-plus" onclick="ubahJumlahSambungan(1)">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                            <small class="form-help">{$lang.cable_connection_count_help}</small>
                        </div>
                        
                        <!-- Container untuk Form Sambungan Dinamis -->
                        <div id="kabelSambunganContainer" style="margin-top: 15px;">
                            <p class="text-muted" style="text-align: center; padding: 10px;"><i class="fa fa-info-circle"></i> {$lang.no_connection_points}</p>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="tutupModalKabel()">
                    <i class="fa fa-times"></i> {$lang.btn_cancel}
                </button>
                <button type="button" class="btn btn-kabel" onclick="simpanKabel()">
                    <i class="fa fa-save"></i> {$lang.btn_save} {$lang.kabel}
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal Detail OLT -->
    <div class="modal-overlay" id="modalDetailOltOverlay">
        <div class="modal-content">
            <div class="modal-header">
                <h4><i class="fa fa-hdd-o"></i> {$lang.modal_detail_edit_olt}</h4>
                <button class="modal-close" onclick="tutupModalDetailOlt()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formEditOlt">
                    <input type="hidden" id="detailOltId" name="olt_id">
                    <input type="hidden" id="detailOltCoordinatesHidden" name="coordinates">
                    
                    <!-- Informasi Dasar -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa fa-info-circle"></i>
                            <span>{$lang.section_basic_info}</span>
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.olt_name_label} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="detailOltName" name="name" placeholder="{$lang.olt_name_placeholder}" required>
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.brand_label}</label>
                            <select class="form-control" id="detailOltBrand" name="brand">
                                <option value="">-- {$lang.brand_option} --</option>
                                <option value="ZTE">ZTE</option>
                                <option value="Huawei">Huawei</option>
                                <option value="C-DATA">C-DATA</option>
                                <option value="Nokia">Nokia</option>
                                <option value="HSGQ">HSGQ</option>
                                <option value="VSOL">VSOL</option>
                                <option value="Lainnya">{$lang.brand_other}</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.form_model}</label>
                            <input type="text" class="form-control" id="detailOltModel" name="model" placeholder="{$lang.model_placeholder}">
                        </div>
                    </div>
                    
                    <!-- Koneksi Parent -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa fa-plug"></i>
                            <span>{$lang.section_parent_connection}</span>
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.connection_type_label}</label>
                            <select class="form-control" id="detailOltParentType" name="parent_type" onchange="toggleDetailParentSelect()">
                                <option value="">-- {$lang.option_no_connection} --</option>
                                <option value="router">{$lang.option_router_mikrotik}</option>
                                <option value="olt">{$lang.option_other_olt}</option>
                            </select>
                        </div>
                        
                        <!-- Dropdown Parent Router -->
                        <div class="form-group" id="detailGroupParentRouter" style="display: none;">
                            <label>{$lang.parent_router_label} <span class="text-danger">*</span></label>
                            <select class="form-control" id="detailOltParentRouter" name="parent_router_id">
                                <option value="">-- {$lang.option_select_router} --</option>
                                {foreach $all_routers as $router}
                                <option value="{$router.id}">{$router.name}</option>
                                {/foreach}
                            </select>
                        </div>
                        
                        <!-- Dropdown Parent OLT -->
                        <div class="form-group" id="detailGroupParentOlt" style="display: none;">
                            <label>{$lang.form_parent_olt} <span class="text-danger">*</span></label>
                            <select class="form-control" id="detailOltParentOltSelect" name="parent_olt_id">
                                <option value="">-- {$lang.option_select_olt} --</option>
                                {foreach $all_olts as $olt_item}
                                <option value="{$olt_item.id}">{$olt_item.name} ({$lang.text_port}: {$olt_item.total_ports - $olt_item.used_ports} {$lang.text_available})</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    
                    <!-- Konfigurasi Port -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa fa-sitemap"></i>
                            <span>{$lang.form_port_configuration}</span>
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.form_total_ports}</label>
                            <div class="port-input-wrapper">
                                <button type="button" class="port-btn port-btn-minus" onclick="ubahDetailPort('total', -1)">
                                    <i class="fa fa-minus"></i>
                                </button>
                                <input type="number" class="form-control port-number" id="detailOltTotalPorts" name="total_ports" value="8" min="1" max="128" readonly>
                                <button type="button" class="port-btn port-btn-plus" onclick="ubahDetailPort('total', 1)">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.form_used_ports}</label>
                            <div class="port-input-wrapper">
                                <button type="button" class="port-btn port-btn-minus" onclick="ubahDetailPort('used', -1)">
                                    <i class="fa fa-minus"></i>
                                </button>
                                <input type="number" class="form-control port-number" id="detailOltUsedPorts" name="used_ports" value="0" min="0" max="128" readonly>
                                <button type="button" class="port-btn port-btn-plus" onclick="ubahDetailPort('used', 1)">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                            <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.olt_used_ports_help}</small>
                        </div>
                        
                        <div class="port-info-simple">
                            {$lang.form_available_ports}: <strong id="detailPortTersedia">0</strong>
                        </div>
                        
                        <!-- Container untuk Multi-Port Fields -->
                        <div id="detailPortsContainer" style="margin-top: 15px;">
                            <!-- {$lang.olt_pon_fields_generated} -->
                        </div>
                    </div>
                    
                    <!-- Identitas Perangkat -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa fa-barcode"></i>
                            <span>{$lang.section_device_identity}</span>
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.serial_number_label}</label>
                            <input type="text" class="form-control" id="detailOltSerialNumber" name="serial_number" placeholder="{$lang.serial_number_placeholder}">
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.ip_address_label}</label>
                            <input type="text" class="form-control" id="detailOltIpAddress" name="ip_address" placeholder="{$lang.ip_address_placeholder}">
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.section_operational_status}</label>
                            <select class="form-control" id="detailOltStatus" name="status">
                                <option value="Active">{$lang.status_active}</option>
                                <option value="Inactive">{$lang.status_inactive}</option>
                                <option value="Maintenance">{$lang.status_maintenance}</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Lokasi & Keterangan -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <i class="fa fa-map-marker"></i>
                            <span>{$lang.section_location_notes}</span>
                        </div>
                        
                        <div class="form-group coordinate-form-group">
                            <label>{$lang.form_map_coordinates} <span class="text-danger">*</span></label>
                            <div class="coordinate-input-wrapper">
                                <button type="button" class="btn-remap-location" onclick="aktifkanModeRemapOlt()">
                                    <i class="fa fa-crosshairs"></i>
                                </button>
                                <input type="text" class="form-control coordinate-input-field" id="detailOltCoordinates" readonly placeholder="{$lang.remap_location_placeholder}">
                            </div>
                            <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.remap_location_help}</small>
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.location_address_label}</label>
                            <textarea class="form-control" id="detailOltAddress" name="address" rows="2" placeholder="{$lang.placeholder_address_example}"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>{$lang.additional_notes_label}</label>
                            <textarea class="form-control" id="detailOltDescription" name="description" rows="2" placeholder="{$lang.placeholder_notes_optional}"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="hapusOlt()" style="margin-right: auto;">
                    <i class="fa fa-trash"></i> {$lang.delete_olt_button}
                </button>
                <button type="button" class="btn btn-secondary" onclick="tutupModalDetailOlt()">
                    <i class="fa fa-times"></i> {$lang.btn_cancel}
                </button>
                <button type="button" class="btn btn-primary" onclick="simpanEditOlt()">
                    <i class="fa fa-save"></i> {$lang.btn_save}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail ODC Box (Multi-Slot View) -->
<div class="modal-overlay" id="modalDetailOdcBoxOverlay">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-odc">
            <h4><i class="fa fa-cube"></i> {$lang.modal_detail_odc_box}</h4>
            <button class="modal-close" onclick="tutupModalDetailOdcBox()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Info Box -->
            <div class="form-section">
                <div class="form-section-header">
                    <i class="fa fa-th-large"></i>
                    <span>{$lang.section_box_info}</span>
                </div>
                <div class="box-info-summary">
                    <div class="box-info-item">
                        <i class="fa fa-map-marker"></i>
                        <span id="boxOdcAddress">-</span>
                    </div>
                    <div class="box-info-item">
                        <i class="fa fa-crosshairs"></i>
                        <span id="boxOdcCoordinates">-</span>
                    </div>
                    <div class="box-info-stats">
                        <div class="stat-item">
                            <span class="stat-value" id="boxOdcTotalSlots">0</span>
                            <span class="stat-label">{$lang.form_slot}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value" id="boxOdcTotalPorts">0</span>
                            <span class="stat-label">{$lang.form_total_ports}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value" id="boxOdcUsedPorts">0</span>
                            <span class="stat-label">{$lang.form_used}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value text-success" id="boxOdcAvailablePorts">0</span>
                            <span class="stat-label">{$lang.form_available}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Daftar Slot -->
            <div class="form-section">
                <div class="form-section-header">
                    <i class="fa fa-folder-open"></i>
                    <span>{$lang.section_odc_slot_list}</span>
                    <button type="button" class="btn btn-sm btn-success" onclick="tambahSlotDariDetail()" style="margin-left: auto;">
                        <i class="fa fa-plus"></i> {$lang.btn_add_slot}
                    </button>
                </div>
                
                <!-- Container untuk Slot Accordion -->
                <div id="boxOdcSlotsContainer" class="slots-accordion">
                    <!-- {$lang.slot_items_generated} -->
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="tutupModalDetailOdcBox()">
                <i class="fa fa-times"></i> {$lang.btn_close}
            </button>
        </div>
    </div>
</div>

<!-- Modal Edit Slot ODC (Single Slot) -->
<div class="modal-overlay" id="modalDetailOdcOverlay">
    <div class="modal-content">
        <div class="modal-header modal-header-odc">
            <h4><i class="fa fa-cube"></i> {$lang.modal_edit_odc}</h4>
            <button class="modal-close" onclick="tutupModalDetailOdc()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formEditOdc">
                <input type="hidden" id="detailOdcId" name="odc_id">
                <input type="hidden" id="detailOdcCoordinatesHidden" name="coordinates">
                <input type="hidden" id="detailOdcSlotNumber" name="slot_number">
                
                <!-- Info Slot -->
                <div class="slot-badge-info">
                    <span class="slot-badge">{$lang.form_slot} <span id="detailOdcSlotBadge">1</span></span>
                </div>
                
                <!-- Informasi Dasar -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-info-circle"></i>
                        <span>{$lang.section_basic_info}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.odc_name_slot_label} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="detailOdcName" name="name" placeholder="{$lang.odc_name_slot_placeholder}" required>
                    </div>
                </div>
                
                <!-- Koneksi Parent -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-plug"></i>
                        <span>{$lang.form_section_parent_connection}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.form_connection_type_label}</label>
                        <select class="form-control" id="detailOdcParentType" name="parent_type" onchange="toggleDetailOdcParentSelect()">
                            <option value="">{$lang.form_no_connection}</option>
                            <option value="olt">{$lang.olt}</option>
                            <option value="odc">{$lang.odc_slot_other}</option>
                        </select>
                    </div>
                    
                    <!-- Dropdown Parent OLT -->
                    <div class="form-group" id="detailOdcGroupParentOlt" style="display: none;">
                        <label>{$lang.form_parent_olt_label} <span class="text-danger">*</span></label>
                        <select class="form-control" id="detailOdcParentOlt" name="parent_olt_id">
                            <option value="">{$lang.form_select_olt}</option>
                            {foreach $all_olts as $olt_item}
                            <option value="{$olt_item.id}">{$olt_item.name}</option>
                            {/foreach}
                        </select>
                    </div>
                    
                    <!-- Dropdown Parent ODC -->
                    <div class="form-group" id="detailOdcGroupParentOdc" style="display: none;">
                        <label>{$lang.form_parent_odc_slot} <span class="text-danger">*</span></label>
                        <select class="form-control" id="detailOdcParentOdcSelect" name="parent_odc_id">
                            <option value="">{$lang.form_select_odc_slot}</option>
                            {foreach $all_odcs as $odc_item}
                            <option value="{$odc_item.id}">{$odc_item.name} ({$lang.text_port}: {$odc_item.total_ports - $odc_item.used_ports} {$lang.text_available})</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                
                <!-- Konfigurasi Port -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-sitemap"></i>
                        <span>{$lang.form_port_configuration}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.form_total_ports}</label>
                        <div class="port-input-wrapper">
                            <button type="button" class="port-btn port-btn-minus" onclick="ubahDetailOdcPort('total', -1)">
                                <i class="fa fa-minus"></i>
                            </button>
                            <input type="number" class="form-control port-number" id="detailOdcTotalPorts" name="total_ports" value="8" min="1" max="128" readonly>
                            <button type="button" class="port-btn port-btn-plus" onclick="ubahDetailOdcPort('total', 1)">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.form_used_ports}</label>
                        <div class="port-input-wrapper">
                            <button type="button" class="port-btn port-btn-minus" onclick="ubahDetailOdcPort('used', -1)">
                                <i class="fa fa-minus"></i>
                            </button>
                            <input type="number" class="form-control port-number" id="detailOdcUsedPorts" name="used_ports" value="0" min="0" max="128" readonly>
                            <button type="button" class="port-btn port-btn-plus" onclick="ubahDetailOdcPort('used', 1)">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.odc_used_ports_help}</small>
                    </div>
                    
                    <div class="port-info-simple">
                        {$lang.form_available_ports}: <strong id="detailOdcPortTersedia">0</strong>
                    </div>
                    
                    <!-- Container untuk Multi-Port Fields -->
                    <div id="detailOdcPortsContainer" style="margin-top: 15px;">
                        <!-- {$lang.odc_port_fields_generated} -->
                    </div>
                </div>
                
                <!-- Status Operasional -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-toggle-on"></i>
                        <span>{$lang.section_operational_status}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.form_status}</label>
                        <select class="form-control" id="detailOdcStatus" name="status">
                            <option value="Active">{$lang.status_active}</option>
                            <option value="Inactive">{$lang.status_inactive}</option>
                            <option value="Maintenance">{$lang.status_maintenance}</option>
                        </select>
                    </div>
                </div>
                
                <!-- Lokasi & Keterangan (hanya untuk Slot 1) -->
                <div class="form-section" id="sectionDetailOdcLokasi">
                    <div class="form-section-header">
                        <i class="fa fa-map-marker"></i>
                        <span>{$lang.section_location_notes}</span>
                    </div>
                    
                    <div class="form-group coordinate-form-group">
                        <label>{$lang.form_map_coordinates} <span class="text-danger">*</span></label>
                        <div class="coordinate-input-wrapper">
                            <button type="button" class="btn-remap-location btn-remap-location-odc" onclick="aktifkanModeRemapOdc()">
                                <i class="fa fa-crosshairs"></i>
                            </button>
                            <input type="text" class="form-control coordinate-input-field" id="detailOdcCoordinates" readonly placeholder="{$lang.remap_location_placeholder}">
                        </div>
                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.remap_location_help}</small>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.location_address_label}</label>
                        <textarea class="form-control" id="detailOdcAddress" name="address" rows="2" placeholder="{$lang.placeholder_address_example}"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.additional_notes_label}</label>
                        <textarea class="form-control" id="detailOdcDescription" name="description" rows="2" placeholder="{$lang.placeholder_notes_optional}"></textarea>
                    </div>
                </div>
                
                <!-- Section Foto ODC (hanya untuk Slot 1) -->
                <div class="form-section" id="sectionDetailOdcFoto">
                    <div class="form-section-header">
                        <i class="fa fa-camera"></i>
                        <span>{$lang.section_odc_photo}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.upload_photo_label} <small class="text-muted">({$lang.photo_max_size_format})</small></label>
                        <input type="hidden" id="editOdcHapusFoto" name="hapus_foto" value="0">
                        <div class="foto-upload-container">
                            <input type="file" id="editOdcFoto" name="foto_odc" accept="image/jpeg,image/jpg,image/png,image/webp" onchange="previewFotoOdcEdit(this)" style="display: none;">
                            <div class="foto-preview-wrapper" id="editOdcFotoPreviewWrapper">
                                <div class="foto-placeholder" id="editOdcFotoPlaceholder" onclick="document.getElementById('editOdcFoto').click()">
                                    <i class="fa fa-camera"></i>
                                    <span>{$lang.click_to_upload_photo}</span>
                                </div>
                                <div class="foto-preview" id="editOdcFotoPreview" style="display: none;">
                                    <img id="editOdcFotoPreviewImg" src="" alt="Preview">
                                    <div class="foto-preview-actions">
                                        <button type="button" class="btn-foto-change" onclick="document.getElementById('editOdcFoto').click()" title="{$lang.change_photo_title}">
                                            <i class="fa fa-refresh"></i>
                                        </button>
                                        <button type="button" class="btn-foto-remove" onclick="hapusFotoOdcEditPreview()" title="{$lang.delete_photo_title}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.photo_auto_resize_help}</small>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="hapusOdc()" style="margin-right: auto;">
                <i class="fa fa-trash"></i> {$lang.delete_slot_button}
            </button>
            <button type="button" class="btn btn-secondary" onclick="tutupModalDetailOdc()">
                <i class="fa fa-times"></i> {$lang.btn_cancel}
            </button>
            <button type="button" class="btn btn-success" onclick="simpanEditOdc()">
                <i class="fa fa-save"></i> {$lang.btn_save}
            </button>
        </div>
    </div>
</div>

<!-- Modal Detail ODP Box (Multi-Slot View) -->
<div class="modal-overlay" id="modalDetailOdpBoxOverlay">
    <div class="modal-content modal-lg">
        <div class="modal-header modal-header-odp">
            <h4><i class="fa fa-circle-o"></i> {$lang.modal_detail_odp_box}</h4>
            <button class="modal-close" onclick="tutupModalDetailOdpBox()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Info Box -->
            <div class="form-section">
                <div class="form-section-header">
                    <i class="fa fa-th-large"></i>
                    <span>{$lang.section_box_info}</span>
                </div>
                <div class="box-info-summary box-info-summary-odp">
                    <div class="box-info-item">
                        <i class="fa fa-map-marker"></i>
                        <span id="boxOdpAddress">-</span>
                    </div>
                    <div class="box-info-item">
                        <i class="fa fa-crosshairs"></i>
                        <span id="boxOdpCoordinates">-</span>
                    </div>
                    <div class="box-info-stats box-info-stats-odp">
                        <div class="stat-item">
                            <span class="stat-value stat-value-odp" id="boxOdpTotalSlots">0</span>
                            <span class="stat-label">{$lang.form_slot}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value stat-value-odp" id="boxOdpTotalPorts">0</span>
                            <span class="stat-label">{$lang.form_total_ports}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value stat-value-odp" id="boxOdpUsedPorts">0</span>
                            <span class="stat-label">{$lang.form_used}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value text-warning" id="boxOdpAvailablePorts">0</span>
                            <span class="stat-label">{$lang.form_available}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Daftar Slot -->
            <div class="form-section">
                <div class="form-section-header">
                    <i class="fa fa-folder-open"></i>
                    <span>{$lang.section_odp_slot_list}</span>
                    <button type="button" class="btn btn-sm btn-warning" onclick="tambahSlotOdpDariDetail()" style="margin-left: auto;">
                        <i class="fa fa-plus"></i> {$lang.btn_add_slot}
                    </button>
                </div>
                
                <!-- Container untuk Slot Accordion -->
                <div id="boxOdpSlotsContainer" class="slots-accordion">
                    <!-- {$lang.slot_items_generated} -->
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="tutupModalDetailOdpBox()">
                <i class="fa fa-times"></i> {$lang.btn_close}
            </button>
        </div>
    </div>
</div>

<!-- Modal Detail ODP -->
<div class="modal-overlay" id="modalDetailOdpOverlay">
    <div class="modal-content">
        <div class="modal-header modal-header-odp">
            <h4><i class="fa fa-circle-o"></i> {$lang.modal_detail_edit_odp}</h4>
            <button class="modal-close" onclick="tutupModalDetailOdp()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formEditOdp">
                <input type="hidden" id="detailOdpId" name="odp_id">
                <input type="hidden" id="detailOdpCoordinatesHidden" name="coordinates">
                <input type="hidden" id="detailOdpSlotNumber" name="slot_number">
                
                <!-- Info Slot -->
                <div class="slot-badge-info slot-badge-info-odp">
                    <span class="slot-badge slot-badge-odp">{$lang.form_slot} <span id="detailOdpSlotBadge">1</span></span>
                </div>
                
                <!-- Informasi Dasar -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-info-circle"></i>
                        <span>{$lang.section_basic_info}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.odp_name_label} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="detailOdpName" name="name" placeholder="{$lang.odp_name_placeholder}" required>
                    </div>
                </div>
                
                <!-- Koneksi Parent -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-plug"></i>
                        <span>{$lang.section_parent_connection}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.connection_type_label}</label>
                        <select class="form-control" id="detailOdpParentType" name="parent_type" onchange="toggleDetailOdpParentSelect()">
                            <option value="">-- {$lang.option_no_connection} --</option>
                            <option value="odc">{$lang.odc}</option>
                            <option value="odp">{$lang.option_other_odp}</option>
                        </select>
                    </div>
                    
                    <!-- Dropdown Parent ODC -->
                    <div class="form-group" id="detailOdpGroupParentOdc" style="display: none;">
                        <label>{$lang.form_parent_odc} <span class="text-danger">*</span></label>
                        <select class="form-control" id="detailOdpParentOdc" name="parent_odc_id">
                            <option value="">-- {$lang.option_select_odc_slot} --</option>
                            {foreach $all_odcs as $odc_item}
                            <option value="{$odc_item.id}">{$odc_item.name}</option>
                            {/foreach}
                        </select>
                    </div>
                    
                    <!-- Dropdown Parent ODP -->
                    <div class="form-group" id="detailOdpGroupParentOdp" style="display: none;">
                        <label>{$lang.form_parent_odp} <span class="text-danger">*</span></label>
                        <select class="form-control" id="detailOdpParentOdpSelect" name="parent_odp_id">
                            <option value="">-- {$lang.option_select_odp_slot} --</option>
                            {foreach $all_odps as $odp_item}
                            <option value="{$odp_item.id}">{$odp_item.name} ({$lang.text_port}: {$odp_item.total_ports - $odp_item.used_ports} {$lang.text_available})</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                
                <!-- Konfigurasi Port -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-sitemap"></i>
                        <span>{$lang.form_port_configuration}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.form_total_ports}</label>
                        <div class="port-input-wrapper">
                            <button type="button" class="port-btn port-btn-minus" onclick="ubahDetailOdpPort('total', -1)">
                                <i class="fa fa-minus"></i>
                            </button>
                            <input type="number" class="form-control port-number" id="detailOdpTotalPorts" name="total_ports" value="8" min="1" max="128" readonly>
                            <button type="button" class="port-btn port-btn-plus" onclick="ubahDetailOdpPort('total', 1)">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.form_used_ports}</label>
                        <div class="port-input-wrapper">
                            <button type="button" class="port-btn port-btn-minus" onclick="ubahDetailOdpPort('used', -1)">
                                <i class="fa fa-minus"></i>
                            </button>
                            <input type="number" class="form-control port-number" id="detailOdpUsedPorts" name="used_ports" value="0" min="0" max="128" readonly>
                            <button type="button" class="port-btn port-btn-plus" onclick="ubahDetailOdpPort('used', 1)">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.odc_used_ports_help}</small>
                    </div>
                    
                    <div class="port-info-simple">
                        {$lang.form_available_ports}: <strong id="detailOdpPortTersedia">0</strong>
                    </div>
                    
                    <!-- Container untuk Multi-Port Fields -->
                    <div id="detailOdpPortsContainer" style="margin-top: 15px;">
                        <!-- {$lang.odp_port_fields_generated} -->
                    </div>
                </div>
                
                <!-- Status Operasional -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-toggle-on"></i>
                        <span>{$lang.section_operational_status}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.form_status}</label>
                        <select class="form-control" id="detailOdpStatus" name="status">
                            <option value="Active">{$lang.status_active}</option>
                            <option value="Inactive">{$lang.status_inactive}</option>
                            <option value="Maintenance">{$lang.status_maintenance}</option>
                        </select>
                    </div>
                </div>
                
                <!-- Lokasi & Keterangan -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-map-marker"></i>
                        <span>{$lang.section_location_notes}</span>
                    </div>
                    
                    <div class="form-group coordinate-form-group">
                        <label>{$lang.form_map_coordinates} <span class="text-danger">*</span></label>
                        <div class="coordinate-input-wrapper">
                            <button type="button" class="btn-remap-location btn-remap-location-odp" onclick="aktifkanModeRemapOdp()">
                                <i class="fa fa-crosshairs"></i>
                            </button>
                            <input type="text" class="form-control coordinate-input-field" id="detailOdpCoordinates" readonly placeholder="{$lang.remap_location_placeholder}">
                        </div>
                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.remap_location_help}</small>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.location_address_label}</label>
                        <textarea class="form-control" id="detailOdpAddress" name="address" rows="2" placeholder="{$lang.placeholder_address_example}"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.additional_notes_label}</label>
                        <textarea class="form-control" id="detailOdpDescription" name="description" rows="2" placeholder="{$lang.placeholder_notes_optional}"></textarea>
                    </div>
                </div>
                
                <!-- Section Foto ODP (hanya untuk slot pertama) -->
                <div class="form-section" id="sectionDetailOdpFoto">
                    <div class="form-section-header">
                        <i class="fa fa-camera"></i>
                        <span>{$lang.section_odp_photo}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.upload_photo_label} <small class="text-muted">({$lang.photo_max_size_format})</small></label>
                        <input type="hidden" id="editOdpHapusFoto" name="hapus_foto" value="0">
                        <div class="foto-upload-container">
                            <input type="file" id="editOdpFoto" name="foto_odp" accept="image/jpeg,image/jpg,image/png,image/webp" onchange="previewFotoOdpEdit(this)" style="display: none;">
                            <div class="foto-preview-wrapper" id="editOdpFotoPreviewWrapper">
                                <div class="foto-placeholder" id="editOdpFotoPlaceholder" onclick="document.getElementById('editOdpFoto').click()">
                                    <i class="fa fa-camera"></i>
                                    <span>{$lang.click_to_upload_photo}</span>
                                </div>
                                <div class="foto-preview" id="editOdpFotoPreview" style="display: none;">
                                    <img id="editOdpFotoPreviewImg" src="" alt="Preview">
                                    <div class="foto-preview-actions">
                                        <button type="button" class="btn-foto-change" onclick="document.getElementById('editOdpFoto').click()" title="{$lang.change_photo_title}">
                                            <i class="fa fa-refresh"></i>
                                        </button>
                                        <button type="button" class="btn-foto-remove" onclick="hapusFotoOdpEditPreview()" title="{$lang.delete_photo_title}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.photo_auto_resize_help}</small>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="hapusOdp()" style="margin-right: auto;">
                <i class="fa fa-trash"></i> {$lang.delete_odp_button}
            </button>
            <button type="button" class="btn btn-secondary" onclick="tutupModalDetailOdp()">
                <i class="fa fa-times"></i> {$lang.btn_cancel}
            </button>
            <button type="button" class="btn btn-warning" onclick="simpanEditOdp()">
                <i class="fa fa-save"></i> {$lang.btn_save}
            </button>
        </div>
    </div>
</div>

<!-- Modal Detail Tiang -->
<div class="modal-overlay" id="modalDetailTiangOverlay">
    <div class="modal-content">
        <div class="modal-header modal-header-tiang">
            <h4><i class="fa fa-minus"></i> {$lang.modal_detail_edit_tiang}</h4>
            <button class="modal-close" onclick="tutupModalDetailTiang()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formEditTiang">
                <input type="hidden" id="detailTiangId" name="tiang_id">
                <input type="hidden" id="detailTiangCoordinatesHidden" name="coordinates">
                
                <!-- Informasi Dasar -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-info-circle"></i>
                        <span>{$lang.section_basic_info}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.form_id_name} {$lang.tiang} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="detailTiangName" name="name" placeholder="{$lang.tiang_name_placeholder}" required>
                    </div>
                </div>
                
                <!-- Informasi Tiang -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-arrows-v"></i>
                        <span>{$lang.section_pole_info}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.pole_length_label}</label>
                        <input type="number" class="form-control" id="detailTiangPanjang" name="panjang" placeholder="{$lang.pole_length_placeholder}" step="0.1" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.pole_material_label}</label>
                        <select class="form-control" id="detailTiangBahan" name="bahan">
                            <option value="">-- {$lang.pole_material_option} --</option>
                            <option value="Besi">{$lang.material_steel}</option>
                            <option value="Beton">{$lang.material_concrete}</option>
                            <option value="Kayu">{$lang.material_wood}</option>
                            <option value="Galvanis">{$lang.material_galvanized}</option>
                            <option value="Komposit">{$lang.material_composite}</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.form_slack_cable} ({$lang.unit_meter})</label>
                        <input type="number" class="form-control" id="detailTiangSlackKabel" name="slack_kabel" placeholder="{$lang.slack_cable_placeholder}" step="0.1" min="0">
                        <small class="form-help">{$lang.slack_cable_help}</small>
                    </div>
                </div>
                
                <!-- Status Operasional -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-toggle-on"></i>
                        <span>{$lang.section_operational_status}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.form_status}</label>
                        <select class="form-control" id="detailTiangStatus" name="status">
                            <option value="Aktif">{$lang.status_active}</option>
                            <option value="Perbaikan">{$lang.status_repair}</option>
                            <option value="Rusak">{$lang.status_damaged}</option>
                        </select>
                    </div>
                </div>
                
                <!-- Lokasi & Keterangan (hanya untuk slot pertama) -->
                <div class="form-section" id="sectionDetailOdpLokasi">
                    <div class="form-section-header">
                        <i class="fa fa-map-marker"></i>
                        <span>{$lang.section_location_notes}</span>
                    </div>
                    
                    <div class="form-group coordinate-form-group">
                        <label>{$lang.form_map_coordinates} <span class="text-danger">*</span></label>
                        <div class="coordinate-input-wrapper">
                            <button type="button" class="btn-remap-location btn-remap-location-tiang" onclick="aktifkanModeRemapTiang()">
                                <i class="fa fa-crosshairs"></i>
                            </button>
                            <input type="text" class="form-control coordinate-input-field" id="detailTiangCoordinates" readonly placeholder="{$lang.remap_location_placeholder}">
                        </div>
                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.remap_location_help}</small>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.location_address_label}</label>
                        <textarea class="form-control" id="detailTiangAddress" name="address" rows="2" placeholder="{$lang.placeholder_address_example}"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.additional_notes_label}</label>
                        <textarea class="form-control" id="detailTiangDescription" name="description" rows="2" placeholder="{$lang.placeholder_notes_optional}"></textarea>
                    </div>
                </div>
                
                <!-- Section Foto Tiang -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-camera"></i>
                        <span>{$lang.section_pole_photo}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.upload_photo_label} <small class="text-muted">({$lang.photo_max_size_format})</small></label>
                        <input type="hidden" id="editTiangHapusFoto" name="hapus_foto" value="0">
                        <div class="foto-upload-container">
                            <input type="file" id="editTiangFoto" name="foto_tiang" accept="image/jpeg,image/jpg,image/png,image/webp" onchange="previewFotoTiangEdit(this)" style="display: none;">
                            <div class="foto-preview-wrapper" id="editTiangFotoPreviewWrapper">
                                <div class="foto-placeholder" id="editTiangFotoPlaceholder" onclick="document.getElementById('editTiangFoto').click()">
                                    <i class="fa fa-camera"></i>
                                    <span>{$lang.click_to_upload_photo}</span>
                                </div>
                                <div class="foto-preview" id="editTiangFotoPreview" style="display: none;">
                                    <img id="editTiangFotoPreviewImg" src="" alt="Preview">
                                    <div class="foto-preview-actions">
                                        <button type="button" class="btn-foto-change" onclick="document.getElementById('editTiangFoto').click()" title="{$lang.change_photo_title}">
                                            <i class="fa fa-refresh"></i>
                                        </button>
                                        <button type="button" class="btn-foto-remove" onclick="hapusFotoTiangEditPreview()" title="{$lang.delete_photo_title}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.photo_auto_resize_help}</small>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="hapusTiang()" style="margin-right: auto;">
                <i class="fa fa-trash"></i> {$lang.delete_pole_button}
            </button>
            <button type="button" class="btn btn-secondary" onclick="tutupModalDetailTiang()">
                <i class="fa fa-times"></i> {$lang.btn_cancel}
            </button>
            <button type="button" class="btn btn-tiang" onclick="simpanEditTiang()">
                <i class="fa fa-save"></i> {$lang.btn_save}
            </button>
        </div>
    </div>
</div>

<!-- Modal Detail Router -->
<div class="modal-overlay" id="modalDetailRouterOverlay">
    <div class="modal-content">
        <div class="modal-header modal-header-router">
            <h4><i class="fa fa-server"></i> {$lang.modal_detail_edit_router}</h4>
            <button class="modal-close" onclick="tutupModalDetailRouter()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formEditRouter">
                <input type="hidden" id="detailRouterId" name="router_id">
                <input type="hidden" id="detailRouterCoordinatesHidden" name="coordinates">
                
                <!-- Informasi Dasar -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-info-circle"></i>
                        <span>{$lang.section_basic_info}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.router_name_label}</label>
                        <input type="text" class="form-control input-readonly" id="detailRouterName" name="name" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.ip_address_label}</label>
                        <input type="text" class="form-control input-readonly" id="detailRouterIpAddress" readonly>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{$lang.router_coverage_label}</label>
                                <input type="number" class="form-control" id="detailRouterCoverage" name="coverage" placeholder="{$lang.router_coverage_placeholder}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{$lang.form_status}</label>
                                <select class="form-control" id="detailRouterEnabled" name="enabled">
                                    <option value="1">{$lang.status_active}</option>
                                    <option value="0">{$lang.status_inactive}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Lokasi & Keterangan -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-map-marker"></i>
                        <span>{$lang.section_location_notes}</span>
                    </div>
                    
                    <div class="form-group coordinate-form-group">
                        <label>{$lang.form_map_coordinates} <span class="text-danger">*</span></label>
                        <div class="coordinate-input-wrapper">
                            <button type="button" class="btn-remap-location btn-remap-location-router" onclick="aktifkanModeRemapRouter()">
                                <i class="fa fa-crosshairs"></i>
                            </button>
                            <input type="text" class="form-control coordinate-input-field" id="detailRouterCoordinates" readonly placeholder="{$lang.remap_button_placeholder}">
                        </div>
                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.remap_location_help}</small>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.notes_label}</label>
                        <textarea class="form-control" id="detailRouterDescription" name="description" rows="3" placeholder="{$lang.placeholder_notes_optional}"></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="hapusRouter()" style="margin-right: auto;">
                <i class="fa fa-trash"></i> {$lang.delete_router_button}
            </button>
            <button type="button" class="btn btn-secondary" onclick="tutupModalDetailRouter()">
                <i class="fa fa-times"></i> {$lang.btn_cancel}
            </button>
            <button type="button" class="btn btn-router" onclick="simpanEditRouter()">
                <i class="fa fa-save"></i> {$lang.btn_save}
            </button>
        </div>
    </div>
</div>

<!-- Modal Detail Kabel -->
<div class="modal-overlay" id="modalDetailKabelOverlay">
    <div class="modal-content">
        <div class="modal-header modal-header-kabel">
            <h4><i class="fa fa-minus"></i> {$lang.modal_detail_edit_cable}</h4>
            <button class="modal-close" onclick="tutupModalDetailKabel()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formEditKabel">
                <input type="hidden" id="detailKabelId" name="kabel_id">
                
                <!-- Informasi Koneksi (Read Only) -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-plug"></i>
                        <span>{$lang.section_connection_info}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.cable_name_label}</label>
                        <input type="text" class="form-control" id="detailKabelName" name="name" placeholder="{$lang.cable_name_placeholder}">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{$lang.form_from_device}</label>
                                <input type="text" class="form-control" id="detailKabelDari" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{$lang.form_to_device}</label>
                                <input type="text" class="form-control" id="detailKabelKe" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informasi Kabel -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-arrows-h"></i>
                        <span>{$lang.section_cable_info}</span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{$lang.form_cable_length} ({$lang.unit_meter})</label>
                                <input type="number" class="form-control" id="detailKabelPanjang" name="panjang" step="0.1" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{$lang.checkpoint_count_label}</label>
                                <input type="text" class="form-control" id="detailKabelCheckpoint" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.form_description}</label>
                        <textarea class="form-control" id="detailKabelDescription" name="description" rows="2" placeholder="{$lang.placeholder_notes_optional}"></textarea>
                    </div>
                </div>
                
                <!-- Sambungan Kabel -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-link"></i>
                        <span>{$lang.section_cable_connection}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.cable_connection_count_label}</label>
                        <div class="port-input-wrapper">
                            <button type="button" class="port-btn port-btn-minus" onclick="ubahJumlahSambunganDetail(-1)">
                                <i class="fa fa-minus"></i>
                            </button>
                            <input type="number" class="form-control port-number" id="detailKabelJumlahSambungan" name="jumlah_sambungan" value="0" min="0" max="20" readonly>
                            <button type="button" class="port-btn port-btn-plus" onclick="ubahJumlahSambunganDetail(1)">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Container untuk Form Sambungan Dinamis -->
                    <div id="detailKabelSambunganContainer" style="margin-top: 15px;">
                        <p class="text-muted" style="text-align: center; padding: 10px;"><i class="fa fa-info-circle"></i> {$lang.no_connection_points}</p>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="hapusKabelDariDetail()" style="margin-right: auto;">
                <i class="fa fa-trash"></i> {$lang.btn_delete}
            </button>
            <button type="button" class="btn btn-secondary" onclick="tutupModalDetailKabel()">
                <i class="fa fa-times"></i> {$lang.btn_cancel}
            </button>
            <button type="button" class="btn btn-kabel" onclick="simpanEditKabel()">
                <i class="fa fa-save"></i> {$lang.btn_save}
            </button>
        </div>
    </div>
</div>

<!-- Modal Tambah Homepass -->
<div class="modal-overlay" id="modalHomepassOverlay">
    <div class="modal-content">
        <div class="modal-header modal-header-homepass">
            <h4><i class="fa fa-home"></i> {$lang.modal_add_homepass}</h4>
            <button class="modal-close" onclick="tutupModalHomepass()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formHomepass">
                <input type="hidden" id="homepassCoordinates" name="coordinates">
                
                <!-- Informasi Pemilik -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-user"></i>
                        <span>{$lang.section_owner_info}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.owner_name_label}</label>
                        <input type="text" class="form-control" id="homepassName" name="name" placeholder="{$lang.owner_name_placeholder}">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{$lang.phone_number_label}</label>
                                <input type="text" class="form-control" id="homepassPhone" name="phone" placeholder="{$lang.phone_optional}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{$lang.building_category_label}</label>
                                <select class="form-control" id="homepassKategori" name="kategori">
                                    <option value="Rumah">{$lang.category_house}</option>
                                    <option value="Ruko">{$lang.category_shophouse}</option>
                                    <option value="Kantor">{$lang.category_office}</option>
                                    <option value="Apartemen">{$lang.category_apartment}</option>
                                    <option value="Kos-kosan">{$lang.category_boarding}</option>
                                    <option value="Lainnya">{$lang.category_other}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Status Survey -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-clipboard"></i>
                        <span>{$lang.section_survey_status}</span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{$lang.form_status}</label>
                                <select class="form-control" id="homepassStatus" name="status">
                                    <option value="Prospek">🔵 {$lang.status_prospect}</option>
                                    <option value="Pending">🟡 {$lang.status_pending}</option>
                                    <option value="Bermasalah">🔴 {$lang.status_problem}</option>
                                    <option value="Tidak Minat">🟠 {$lang.status_not_interested}</option>
                                    <option value="Rumah Kosong">⚫ {$lang.status_empty_house}</option>
                                    <option value="Sudah Langganan">🟢 {$lang.status_subscribed}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{$lang.survey_date_label}</label>
                                <input type="date" class="form-control" id="homepassTanggal" name="tanggal_survey">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.surveyor_name_label}</label>
                        <input type="text" class="form-control" id="homepassSurveyor" name="surveyor" placeholder="{$lang.surveyor_name_placeholder}" value="{$_admin.fullname}">
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.survey_notes_label}</label>
                        <textarea class="form-control" id="homepassCatatan" name="catatan" rows="3" placeholder="{$lang.survey_notes_placeholder}"></textarea>
                    </div>
                </div>
                
                <!-- Informasi Lokasi -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-map-marker"></i>
                        <span>{$lang.section_survey_location}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.form_coordinates} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="homepassCoordinatesDisplay" readonly placeholder="{$lang.placeholder_auto_fill_map}">
                        <small class="form-help">{$lang.homepass_coordinates_help}</small>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.full_address_label}</label>
                        <textarea class="form-control" id="homepassAddress" name="address" rows="2" placeholder="{$lang.full_address_placeholder}"></textarea>
                    </div>
                </div>
                
                <!-- Section Foto Homepass -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-camera"></i>
                        <span>{$lang.section_location_photo}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.upload_photo_label} <small class="text-muted">({$lang.photo_max_size_format})</small></label>
                        <div class="foto-upload-container">
                            <input type="file" id="addHomepassFoto" name="foto_homepass" accept="image/jpeg,image/jpg,image/png,image/webp" onchange="previewFotoHomepassAdd(this)" style="display: none;">
                            <div class="foto-preview-wrapper" id="addHomepassFotoPreviewWrapper">
                                <div class="foto-placeholder" id="addHomepassFotoPlaceholder" onclick="document.getElementById('addHomepassFoto').click()">
                                    <i class="fa fa-camera"></i>
                                    <span>{$lang.click_to_upload_photo}</span>
                                </div>
                                <div class="foto-preview" id="addHomepassFotoPreview" style="display: none;">
                                    <img id="addHomepassFotoPreviewImg" src="" alt="Preview">
                                    <div class="foto-preview-actions">
                                        <button type="button" class="btn-foto-change" onclick="document.getElementById('addHomepassFoto').click()" title="{$lang.change_photo_title}">
                                            <i class="fa fa-refresh"></i>
                                        </button>
                                        <button type="button" class="btn-foto-remove" onclick="hapusFotoHomepassAddPreview()" title="{$lang.delete_photo_title}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.location_photo_help}</small>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="tutupModalHomepass()">
                <i class="fa fa-times"></i> {$lang.btn_cancel}
            </button>
            <button type="button" class="btn btn-homepass" onclick="simpanHomepass()">
                <i class="fa fa-save"></i> {$lang.btn_save} {$lang.homepass}
            </button>
        </div>
    </div>
</div>

<!-- Modal Detail Homepass -->
<div class="modal-overlay" id="modalDetailHomepassOverlay">
    <div class="modal-content">
        <div class="modal-header modal-header-homepass">
            <h4><i class="fa fa-home"></i> {$lang.modal_detail_homepass}</h4>
            <button class="modal-close" onclick="tutupModalDetailHomepass()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formDetailHomepass">
                <input type="hidden" id="detailHomepassId" name="homepass_id">
                <input type="hidden" id="detailHomepassCoordinates" name="coordinates">
                
                <!-- Informasi Pemilik -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-user"></i>
                        <span>{$lang.section_owner_info}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.owner_name_label}</label>
                        <input type="text" class="form-control" id="detailHomepassName" name="name" placeholder="{$lang.owner_name_placeholder}">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{$lang.phone_number_label}</label>
                                <input type="text" class="form-control" id="detailHomepassPhone" name="phone" placeholder="{$lang.phone_optional}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{$lang.building_category_label}</label>
                                <select class="form-control" id="detailHomepassKategori" name="kategori">
                                    <option value="Rumah">{$lang.category_house}</option>
                                    <option value="Ruko">{$lang.category_shophouse}</option>
                                    <option value="Kantor">{$lang.category_office}</option>
                                    <option value="Apartemen">{$lang.category_apartment}</option>
                                    <option value="Kos-kosan">{$lang.category_boarding}</option>
                                    <option value="Lainnya">{$lang.category_other}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Status Survey -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-clipboard"></i>
                        <span>{$lang.section_survey_status}</span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{$lang.form_status}</label>
                                <select class="form-control" id="detailHomepassStatus" name="status">
                                    <option value="Prospek">🔵 {$lang.status_prospect}</option>
                                    <option value="Pending">🟡 {$lang.status_pending}</option>
                                    <option value="Bermasalah">🔴 {$lang.status_problem}</option>
                                    <option value="Tidak Minat">🟠 {$lang.status_not_interested}</option>
                                    <option value="Rumah Kosong">⚫ {$lang.status_empty_house}</option>
                                    <option value="Sudah Langganan">🟢 {$lang.status_subscribed}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{$lang.survey_date_label}</label>
                                <input type="date" class="form-control" id="detailHomepassTanggal" name="tanggal_survey">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.surveyor_name_label}</label>
                        <input type="text" class="form-control" id="detailHomepassSurveyor" name="surveyor">
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.survey_notes_label}</label>
                        <textarea class="form-control" id="detailHomepassCatatan" name="catatan" rows="3"></textarea>
                    </div>
                </div>
                
                <!-- Informasi Lokasi -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-map-marker"></i>
                        <span>{$lang.section_survey_location}</span>
                    </div>
                    
                    <div class="form-group coordinate-form-group">
                        <label>{$lang.form_map_coordinates} <span class="text-danger">*</span></label>
                        <div class="coordinate-input-wrapper">
                            <button type="button" class="btn-remap-location btn-remap-location-homepass" onclick="remapHomepass()">
                                <i class="fa fa-crosshairs"></i>
                            </button>
                            <input type="text" class="form-control coordinate-input-field" id="detailHomepassCoordinatesDisplay" readonly placeholder="{$lang.remap_button_placeholder}">
                        </div>
                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.remap_location_help}</small>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.full_address_label}</label>
                        <textarea class="form-control" id="detailHomepassAddress" name="address" rows="2"></textarea>
                    </div>
                </div>
                
                <!-- Section Foto Homepass -->
                <div class="form-section">
                    <div class="form-section-header">
                        <i class="fa fa-camera"></i>
                        <span>{$lang.section_location_photo}</span>
                    </div>
                    
                    <div class="form-group">
                        <label>{$lang.upload_photo_label} <small class="text-muted">({$lang.photo_max_size_format})</small></label>
                        <input type="hidden" id="editHomepassHapusFoto" name="hapus_foto" value="0">
                        <div class="foto-upload-container">
                            <input type="file" id="editHomepassFoto" name="foto_homepass" accept="image/jpeg,image/jpg,image/png,image/webp" onchange="previewFotoHomepassEdit(this)" style="display: none;">
                            <div class="foto-preview-wrapper" id="editHomepassFotoPreviewWrapper">
                                <div class="foto-placeholder" id="editHomepassFotoPlaceholder" onclick="document.getElementById('editHomepassFoto').click()">
                                    <i class="fa fa-camera"></i>
                                    <span>{$lang.click_to_upload_photo}</span>
                                </div>
                                <div class="foto-preview" id="editHomepassFotoPreview" style="display: none;">
                                    <img id="editHomepassFotoPreviewImg" src="" alt="Preview">
                                    <div class="foto-preview-actions">
                                        <button type="button" class="btn-foto-change" onclick="document.getElementById('editHomepassFoto').click()" title="{$lang.change_photo_title}">
                                            <i class="fa fa-refresh"></i>
                                        </button>
                                        <button type="button" class="btn-foto-remove" onclick="hapusFotoHomepassEditPreview()" title="{$lang.delete_photo_title}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <small class="form-help"><i class="fa fa-info-circle"></i> {$lang.location_photo_help}</small>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="hapusHomepass()" style="margin-right: auto;">
                <i class="fa fa-trash"></i> {$lang.btn_delete}
            </button>
            <button type="button" class="btn btn-secondary" onclick="tutupModalDetailHomepass()">
                <i class="fa fa-times"></i> {$lang.btn_cancel}
            </button>
            <button type="button" class="btn btn-homepass" onclick="updateHomepass()">
                <i class="fa fa-save"></i> {$lang.btn_save}
            </button>
        </div>
    </div>
</div>

<!-- Modern Uninstall Modal -->
<div class="modal-overlay-ffth" id="uninstallModalOverlay" style="display: none;">
    <div class="modal-container-ffth uninstall-modal-ffth">
        <div class="modal-header-danger-ffth">
            <h4><i class="fa fa-exclamation-triangle"></i> {$lang.modal_uninstall_title}</h4>
            <button class="modal-close-btn-ffth" onclick="closeUninstallModal()">&times;</button>
        </div>
        <div class="modal-body-ffth">
            <!-- Warning Section -->
            <div class="warning-box-ffth">
                <div class="warning-icon-ffth">
                    <i class="fa fa-exclamation-circle"></i>
                </div>
                <div class="warning-content-ffth">
                    <strong>{$lang.alert_warning}</strong>
                    <p>{$lang.uninstall_warning_text}</p>
                </div>
            </div>
            
            <!-- Delete List Section -->
            <div class="form-section-ffth">
                <div class="form-section-header-ffth">
                    <i class="fa fa-trash"></i>
                    <span>{$lang.section_will_be_deleted}</span>
                </div>
                
                <div class="delete-list-grid-ffth">
                    <div class="delete-item-ffth">
                        <i class="fa fa-database text-danger"></i>
                        <span>{$lang.delete_item_tables}</span>
                    </div>
                    <div class="delete-item-ffth">
                        <i class="fa fa-home text-danger"></i>
                        <span>{$lang.delete_item_homepass}</span>
                    </div>
                    <div class="delete-item-ffth">
                        <i class="fa fa-users text-danger"></i>
                        <span>{$lang.delete_item_customer_status}</span>
                    </div>
                    <div class="delete-item-ffth">
                        <i class="fa fa-link text-danger"></i>
                        <span>{$lang.delete_item_odp_column}</span>
                    </div>
                    <div class="delete-item-ffth">
                        <i class="fa fa-image text-danger"></i>
                        <span>{$lang.delete_item_photos}</span>
                    </div>
                    <div class="delete-item-ffth">
                        <i class="fa fa-clock-o text-danger"></i>
                        <span>{$lang.delete_item_cron_files}</span>
                    </div>
                    <div class="delete-item-ffth">
                        <i class="fa fa-code text-danger"></i>
                        <span>{$lang.delete_item_plugin_files}</span>
                    </div>
                    <div class="delete-item-ffth">
                        <i class="fa fa-paint-brush text-danger"></i>
                        <span>{$lang.delete_item_ui_mods}</span>
                    </div>
                </div>
            </div>
            
            <!-- Confirmation Section -->
            <div class="form-section-ffth">
                <div class="form-section-header-ffth">
                    <i class="fa fa-check-circle"></i>
                    <span>{$lang.confirm}</span>
                </div>
                
                <div class="form-group-ffth">
                    <label>{$lang.uninstall_type_instruction} <code class="confirm-code-ffth">UNINSTALL</code> {$lang.uninstall_type_suffix}</label>
                    <input type="text" class="form-control-ffth" id="uninstall_confirm" 
                        placeholder="{$lang.uninstall_type_placeholder}" autocomplete="off">
                </div>
            </div>
            
            <!-- Progress Log (Hidden initially) -->
            <div class="form-section-ffth" id="uninstall-progress-section" style="display: none;">
                <div class="form-section-header-ffth">
                    <i class="fa fa-terminal"></i>
                    <span>{$lang.uninstall_progress_title}</span>
                </div>
                <div class="progress-log-container-ffth" id="uninstall-log-content">
                </div>
            </div>
        </div>
        <div class="modal-footer-ffth">
            <button type="button" class="btn-cancel-ffth" onclick="closeUninstallModal()" id="btn-cancel-uninstall">
                <i class="fa fa-times"></i> {$lang.btn_cancel}
            </button>
            <button type="button" class="btn-danger-ffth" onclick="runUninstall()" id="btn-run-uninstall">
                <i class="fa fa-trash"></i> {$lang.btn_uninstall_plugin}
            </button>
        </div>
    </div>
</div>

<!-- Update Modal -->
<div class="modal-overlay-ffth" id="updateModalOverlay" style="display: none;">
    <div class="modal-container-ffth update-modal-ffth">
        <div class="modal-header-update-ffth">
            <h4><i class="fa fa-download"></i> <span id="update-modal-title">{$lang.update_available_title}</span></h4>
            <button class="modal-close-btn-ffth" onclick="closeUpdateModal()">&times;</button>
        </div>
        <div class="modal-body-ffth">
            <!-- Version Info -->
            <div class="update-version-box-ffth">
                <div class="version-current-ffth">
                    <span class="version-label-ffth">{$lang.current_version_label}</span>
                    <span class="version-number-ffth" id="current-version">v1.0.0</span>
                </div>
                <div class="version-arrow-ffth">
                    <i class="fa fa-arrow-right"></i>
                </div>
                <div class="version-new-ffth">
                    <span class="version-label-ffth">{$lang.new_version_label}</span>
                    <span class="version-number-ffth" id="new-version">v1.0.1</span>
                </div>
            </div>
            
            <!-- Changelog Section -->
            <div class="form-section-ffth">
                <div class="form-section-header-ffth">
                    <i class="fa fa-list-ul"></i>
                    <span>{$lang.whats_new_title}</span>
                </div>
                <div class="changelog-list-ffth" id="changelog-list">
                    <!-- Changelog items will be inserted here -->
                </div>
            </div>
            
            <!-- Progress Log (Hidden initially) -->
            <div class="form-section-ffth" id="update-progress-section" style="display: none;">
                <div class="form-section-header-ffth">
                    <i class="fa fa-terminal"></i>
                    <span>{$lang.update_progress_title}</span>
                </div>
                <div class="progress-log-container-ffth" id="update-log-content">
                </div>
            </div>
        </div>
        <div class="modal-footer-ffth">
            <button type="button" class="btn-cancel-ffth" onclick="closeUpdateModal()" id="btn-cancel-update">
                <i class="fa fa-times"></i> {$lang.btn_later}
            </button>
            <button type="button" class="btn-update-ffth" onclick="runUpdate()" id="btn-run-update">
                <i class="fa fa-download"></i> {$lang.btn_update_now}
            </button>
        </div>
    </div>
</div>

<script>
// Load Language dari PHP ke JavaScript
var LANG = {$lang|@json_encode};

// Set data from PHP
var baseUrl = '{$_url}';

// Functions for editing from map popup
function editOdcFromMap(id) {
    var protocol = window.location.protocol;
    var host = window.location.host;
    var editUrl = protocol + '//' + host + '/?_route=plugin/network_mapping/edit-odc&id=' + id;
    
    console.log('Edit ODC from map, ID:', id);
    window.location.href = editUrl;
}

function editOdpFromMap(id) {
    var protocol = window.location.protocol;
    var host = window.location.host;
    var editUrl = protocol + '//' + host + '/?_route=plugin/network_mapping/edit-odp&id=' + id;
    
    console.log('Edit ODP from map, ID:', id);
    window.location.href = editUrl;
}
</script>

<!-- Load external JavaScript -->
<script>
function formatMeter(value) {
    if (!value || value == 0) {
        return '-';
    }
    var floatVal = parseFloat(value);
    
    // Jika >= 1000 meter, konversi ke KM
    if (floatVal >= 1000) {
        var km = floatVal / 1000;
        if (km === Math.floor(km)) {
            return km + ' km';
        } else {
            var formatted = km.toFixed(2).replace('.', ',').replace(/,?0+$/, '');
            return formatted + ' km';
        }
    }
    
    // Jika < 1000 meter
    if (floatVal === Math.floor(floatVal)) {
        return floatVal + ' meter';
    } else {
        var formatted = floatVal.toFixed(2).replace('.', ',').replace(/,?0+$/, '');
        return formatted + ' meter';
    }
}

// Variabel pemetaan jaringan
var map;
var markersGroup = {
    routers: L.layerGroup(),
    olts: L.layerGroup(),
    sambunganKabel: L.layerGroup(),
    odcs: L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        disableClusteringAtZoom: 18,
        iconCreateFunction: function(cluster) {
            var count = cluster.getChildCount();
            var size = 'small';
            var iconSize = 36;
            
            if (count > 50) {
                size = 'large';
                iconSize = 52;
            } else if (count > 10) {
                size = 'medium';
                iconSize = 44;
            }
            
            return L.divIcon({
                html: '<div class="cluster-icon cluster-odc-' + size + '">' + count + '</div>',
                className: 'odc-cluster',
                iconSize: L.point(iconSize, iconSize)
            });
        }
    }),
    odps: L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        disableClusteringAtZoom: 18,
        iconCreateFunction: function(cluster) {
            var count = cluster.getChildCount();
            var size = 'small';
            var iconSize = 36;
            
            if (count > 50) {
                size = 'large';
                iconSize = 52;
            } else if (count > 10) {
                size = 'medium';
                iconSize = 44;
            }
            
            return L.divIcon({
                html: '<div class="cluster-icon cluster-odp-' + size + '">' + count + '</div>',
                className: 'odp-cluster',
                iconSize: L.point(iconSize, iconSize)
            });
        }
    }),
    tiang: L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        disableClusteringAtZoom: 18,
        iconCreateFunction: function(cluster) {
            var count = cluster.getChildCount();
            var iconSize, bgColor, fontSize, paddingTop;
            
            if (count > 50) {
                // Large
                iconSize = 52;
                bgColor = '#713f12';
                fontSize = 14;
                paddingTop = 18;
            } else if (count > 10) {
                // Medium
                iconSize = 44;
                bgColor = '#854d0e';
                fontSize = 12;
                paddingTop = 15;
            } else {
                // Small
                iconSize = 36;
                bgColor = '#a16207';
                fontSize = 10;
                paddingTop = 12;
            }
            
            var height = Math.round(iconSize * 0.866);
            var borderSize = iconSize + 10;
            var borderHeight = Math.round(borderSize * 0.866);
            
            var borderStyle = [
                'position:absolute',
                'top:-5px',
                'left:-5px',
                'width:' + borderSize + 'px',
                'height:' + borderHeight + 'px',
                'background-color:white',
                'clip-path:polygon(50% 0%, 0% 100%, 100% 100%)',
                'filter:drop-shadow(0 3px 6px rgba(0,0,0,0.3))'
            ].join(';');
            
            var innerStyle = [
                'position:relative',
                'display:flex',
                'align-items:center',
                'justify-content:center',
                'width:' + iconSize + 'px',
                'height:' + height + 'px',
                'background-color:' + bgColor,
                'clip-path:polygon(50% 0%, 0% 100%, 100% 100%)',
                'color:white',
                'font-weight:bold',
                'font-size:' + fontSize + 'px',
                'padding-top:' + paddingTop + 'px',
                'text-shadow:1px 1px 2px rgba(0,0,0,0.5)'
            ].join(';');
            
            var wrapperStyle = 'position:relative;width:' + iconSize + 'px;height:' + height + 'px;';
            
            return L.divIcon({
                html: '<div style="' + wrapperStyle + '"><div style="' + borderStyle + '"></div><div style="' + innerStyle + '">' + count + '</div></div>',
                className: 'tiang-cluster',
                iconSize: L.point(iconSize, iconSize)
            });
        }
    }),
    kabel: L.layerGroup(),
    homepass: L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        disableClusteringAtZoom: 18,
        iconCreateFunction: function(cluster) {
            var count = cluster.getChildCount();
            var size = 'small';
            var iconSize = 36;
            
            if (count > 50) {
                size = 'large';
                iconSize = 52;
            } else if (count > 10) {
                size = 'medium';
                iconSize = 44;
            }
            
            return L.divIcon({
                html: '<div class="cluster-icon cluster-homepass-' + size + '">' + count + '</div>',
                className: 'homepass-cluster',
                iconSize: L.point(iconSize, iconSize)
            });
        }
    }),
    customers: L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        disableClusteringAtZoom: 18,
        iconCreateFunction: function(cluster) {
            var count = cluster.getChildCount();
            var size = 'small';
            var iconSize = 36;
            
            if (count > 50) {
                size = 'large';
                iconSize = 52;
            } else if (count > 10) {
                size = 'medium';
                iconSize = 44;
            }
            
            return L.divIcon({
                html: '<div class="cluster-icon cluster-' + size + '">' + count + '</div>',
                className: 'customer-cluster',
                iconSize: L.point(iconSize, iconSize)
            });
        }
    })
};
var connectionsGroup = L.layerGroup();
var networkData = {$network_data|json_encode};

// Fungsi Toggle Floating Action Button Menu
function toggleFabMenu() {
    var fabMain = document.getElementById('fabMain');
    var fabMenu = document.getElementById('fabMenu');
    var fabIcon = document.getElementById('fabIcon');
    
    fabMain.classList.toggle('active');
    fabMenu.classList.toggle('active');
    
    if (fabMain.classList.contains('active')) {
        fabIcon.classList.remove('fa-plus');
        fabIcon.classList.add('fa-times');
    } else {
        fabIcon.classList.remove('fa-times');
        fabIcon.classList.add('fa-plus');
    }
}

// Fungsi Toggle Grup (Collapse/Expand)
function toggleGrup(grupId) {
    var grupContent = document.getElementById(grupId);
    var arrow = document.getElementById('arrow' + grupId.charAt(0).toUpperCase() + grupId.slice(1));
    
    if (grupContent) {
        grupContent.classList.toggle('collapsed');
    }
    
    if (arrow) {
        arrow.classList.toggle('collapsed');
    }
}

// Fungsi untuk cek dan set default collapse di mobile
function aturDefaultCollapseMobile() {
    var lebarLayar = window.innerWidth;
    var grupLapisan = document.getElementById('grupLapisan');
    var arrowLapisan = document.getElementById('arrowGrupLapisan');
    
    if (lebarLayar <= 768) {
        // Mobile: default collapse grup lapisan
        if (grupLapisan && !grupLapisan.classList.contains('collapsed')) {
            grupLapisan.classList.add('collapsed');
        }
        if (arrowLapisan && !arrowLapisan.classList.contains('collapsed')) {
            arrowLapisan.classList.add('collapsed');
        }
    } else {
        // Desktop: default expand grup lapisan
        if (grupLapisan && grupLapisan.classList.contains('collapsed')) {
            grupLapisan.classList.remove('collapsed');
        }
        if (arrowLapisan && arrowLapisan.classList.contains('collapsed')) {
            arrowLapisan.classList.remove('collapsed');
        }
    }
}

// Jalankan saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    aturDefaultCollapseMobile();
});

// Jalankan saat ukuran layar berubah (rotate atau resize)
window.addEventListener('resize', function() {
    aturDefaultCollapseMobile();
});

// Variabel untuk mode pick
var isPickMode = false;
var pickModeType = null;
var tempMarker = null;

// Variabel untuk mode kabel
var isKabelMode = false;
var kabelCheckpoints = []; // Array koordinat checkpoint
var kabelStartDevice = null; // Object: type, id, name, coordinates
var kabelEndDevice = null; // Object: type, id, name, coordinates
var kabelTempLine = null; // Garis sementara saat drawing
var kabelPreviewLines = []; // Array garis preview antar checkpoint
var kabelCheckpointMarkers = []; // Array marker checkpoint

// Fungsi untuk membuka modal tambah
function bukaModalTambah(tipe) {
    // Tutup menu floating dulu
    toggleFabMenu();
    
    if (tipe === 'olt') {
        // Aktifkan mode pick untuk OLT
        aktifkanModePick('olt');
    } else if (tipe === 'odc') {
        // Aktifkan mode pick untuk ODC
        aktifkanModePick('odc');
    } else if (tipe === 'odp') {
        // Aktifkan mode pick untuk ODP
        aktifkanModePick('odp');
    } else if (tipe === 'tiang') {
        // Aktifkan mode pick untuk Tiang
        aktifkanModePick('tiang');
    } else if (tipe === 'kabel') {
        // Aktifkan mode kabel
        aktifkanModeKabel();
    } else if (tipe === 'homepass') {
        // Aktifkan mode pick untuk Homepass
        aktifkanModePick('homepass');
    } else {
        // Placeholder untuk tipe lainnya
        Swal.fire({
            icon: 'info',
            title: LANG.btn_add + ' ' + tipe.toUpperCase(),
            text: LANG.feature_coming_soon.replace('__TYPE__', tipe.toUpperCase()),
            confirmButtonText: LANG.btn_ok,
            confirmButtonColor: '#6366f1'
        });
    }
}

// Fungsi untuk aktifkan mode pick
function aktifkanModePick(tipe) {
    isPickMode = true;
    pickModeType = tipe;
    
    // Tampilkan indikator dengan teks sesuai tipe
    var pickIndicator = document.getElementById('pickModeIndicator');
    var pickText = pickIndicator.querySelector('span');
    
    if (tipe === 'olt') {
        pickText.textContent = LANG.pick_location_olt;
    } else if (tipe === 'odc') {
        pickText.textContent = LANG.pick_location_odc;
    } else if (tipe === 'odp') {
        pickText.textContent = LANG.pick_location_odp;
    } else if (tipe === 'tiang') {
        pickText.textContent = LANG.pick_location_tiang;
    } else if (tipe === 'homepass') {
        pickText.textContent = LANG.pick_location_homepass;
    } else if (tipe === 'remap-router') {
        pickText.textContent = LANG.pick_location_router_new;
    } else {
        pickText.textContent = LANG.pick_location_general;
    }
    
    pickIndicator.classList.add('active');
    
    // Ubah cursor map
    document.getElementById('map').style.cursor = 'crosshair';
    
    // Tambahkan event listener untuk klik map dengan delay
    // Delay agar klik tombol tidak langsung menembus ke map
    setTimeout(function() {
        map.on('click', onMapClickPick);
    }, 300);
}

// Fungsi untuk batalkan mode pick
function batalkanModePick() {
    isPickMode = false;
    pickModeType = null;
    
    // Sembunyikan indikator
    document.getElementById('pickModeIndicator').classList.remove('active');
    
    // Kembalikan cursor
    document.getElementById('map').style.cursor = '';
    
    // Hapus temp marker jika ada
    if (tempMarker) {
        map.removeLayer(tempMarker);
        tempMarker = null;
    }
    
    // Hapus event listener
    map.off('click', onMapClickPick);
}

// Fungsi ketika map diklik saat mode pick
function onMapClickPick(e) {
    var lat = e.latlng.lat.toFixed(6);
    var lng = e.latlng.lng.toFixed(6);
    var coordinates = lat + ',' + lng;
    
    // Hapus temp marker sebelumnya jika ada
    if (tempMarker) {
        map.removeLayer(tempMarker);
    }
    
    // Buat temp marker sesuai tipe
    var tempIconHtml = '<div class="olt-marker"><i class="fa fa-hdd-o"></i></div>';
    if (pickModeType === 'odc' || pickModeType === 'remap-odc') {
        tempIconHtml = '<div class="odc-marker"><i class="fa fa-cube"></i></div>';
    } else if (pickModeType === 'odp' || pickModeType === 'remap-odp') {
        tempIconHtml = '<div class="odp-marker"><i class="fa fa-circle-o"></i></div>';
    } else if (pickModeType === 'tiang' || pickModeType === 'remap-tiang') {
        tempIconHtml = '<div class="tiang-marker"><i class="fa fa-minus"></i></div>';
    } else if (pickModeType === 'homepass' || pickModeType === 'remap-homepass') {
        tempIconHtml = '<div class="homepass-marker" style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);"><i class="fa fa-home"></i></div>';
    }
    
    var tempIcon = L.divIcon({
        className: 'custom-div-icon',
        html: tempIconHtml,
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });
    
    tempMarker = L.marker([lat, lng], { icon: tempIcon }).addTo(map);
    
    // Nonaktifkan mode pick
    isPickMode = false;
    document.getElementById('pickModeIndicator').classList.remove('active');
    document.getElementById('map').style.cursor = '';
    map.off('click', onMapClickPick);
    
    // Buka modal sesuai tipe
    if (pickModeType === 'olt') {
        bukaModalOlt(coordinates);
    }
    // Mode remapping untuk EDIT OLT
    else if (pickModeType === 'remap-olt') {
        // Buka kembali modal detail dengan koordinat baru
        bukaKembaliModalDetailOlt(coordinates);
    }
    // Buka modal ODC
    else if (pickModeType === 'odc') {
        bukaModalOdc(coordinates);
    }
    // Mode remapping untuk EDIT ODC
    else if (pickModeType === 'remap-odc') {
        // Buka kembali modal detail dengan koordinat baru
        bukaKembaliModalDetailOdc(coordinates);
    }
    // Buka modal ODP
    else if (pickModeType === 'odp') {
        bukaModalOdp(coordinates);
    }
    // Mode remapping untuk EDIT ODP
    else if (pickModeType === 'remap-odp') {
        // Buka kembali modal detail dengan koordinat baru
        bukaKembaliModalDetailOdp(coordinates);
    }
    // Buka modal Tiang
    else if (pickModeType === 'tiang') {
        bukaModalTiang(coordinates);
    }
    // Mode remapping untuk EDIT Tiang
    else if (pickModeType === 'remap-tiang') {
        // Buka kembali modal detail dengan koordinat baru
        bukaKembaliModalDetailTiang(coordinates);
    }
    // Buka modal Homepass
    else if (pickModeType === 'homepass') {
        bukaModalHomepass(coordinates);
    }
    // Mode remapping untuk EDIT Homepass
    else if (pickModeType === 'remap-homepass') {
        bukaKembaliModalDetailHomepass(coordinates);
    }
    // Mode remapping untuk EDIT Router
    else if (pickModeType === 'remap-router') {
        bukaKembaliModalDetailRouter(coordinates);
    }
}

// Fungsi untuk buka modal OLT
function bukaModalOlt(coordinates) {
    document.getElementById('oltCoordinates').value = coordinates;
    document.getElementById('oltCoordinatesDisplay').value = coordinates;
    document.getElementById('modalOltOverlay').classList.add('active');
    
    // Reset parent selection
    document.getElementById('oltParentType').value = '';
    toggleParentSelect();
    
    // Reset dan inisialisasi multi-port fields
    resetAddPortFields();
    updatePortTersediaAdd();
}

// Fungsi untuk tutup modal OLT
function tutupModalOlt() {
    document.getElementById('modalOltOverlay').classList.remove('active');
    document.getElementById('formOlt').reset();
    document.getElementById('oltTotalPorts').value = 8;
    document.getElementById('oltUsedPorts').value = 0;
    
    // Reset parent selection dan port detail
    document.getElementById('oltParentType').value = '';
    toggleParentSelect();
    
    // Reset multi-port fields
    resetAddPortFields();
    updatePortTersediaAdd();
    
    // Hapus temp marker
    if (tempMarker) {
        map.removeLayer(tempMarker);
        tempMarker = null;
    }
}

// ==================== FUNGSI MULTI-PORT UNTUK MODAL ADD OLT ====================

// Variable untuk menyimpan ports data di modal Add
window.addOltPortsData = [];

// Fungsi untuk ubah port dengan tombol +/- di Modal Add OLT
function ubahPortAdd(tipe, delta) {
    var inputId = tipe === 'total' ? 'oltTotalPorts' : 'oltUsedPorts';
    var input = document.getElementById(inputId);
    var nilai = parseInt(input.value) || 0;
    
    var totalPorts = parseInt(document.getElementById('oltTotalPorts').value) || 8;
    var usedPorts = parseInt(document.getElementById('oltUsedPorts').value) || 0;
    
    if (tipe === 'total') {
        nilai += delta;
        if (nilai < 1) nilai = 1;
        if (nilai > 128) nilai = 128;
        // Total port tidak boleh kurang dari used ports
        if (nilai < usedPorts) nilai = usedPorts;
        input.value = nilai;
        
        // Jika total port berubah, regenerate semua dropdown PON dengan opsi baru
        updateAddPortsDataFromFields();
        generateAddPortFields(usedPorts, nilai);
    } else {
        // Used ports
        nilai += delta;
        if (nilai < 0) nilai = 0;
        if (nilai > totalPorts) nilai = totalPorts;
        input.value = nilai;
        
        // Regenerate port fields
        generateAddPortFields(nilai, totalPorts);
    }
    
    // Update tampilan port tersedia
    updatePortTersediaAdd();
}

// Fungsi untuk update tampilan port tersedia di modal Add
function updatePortTersediaAdd() {
    var totalPorts = parseInt(document.getElementById('oltTotalPorts').value) || 0;
    var usedPorts = parseInt(document.getElementById('oltUsedPorts').value) || 0;
    var tersedia = totalPorts - usedPorts;
    
    var portTersediaElement = document.getElementById('portTersedia');
    if (portTersediaElement) {
        portTersediaElement.textContent = tersedia;
        
        // Ubah warna background berdasarkan availability
        var portInfoSimple = portTersediaElement.closest('.port-info-simple');
        if (portInfoSimple) {
            if (tersedia === 0) {
                portInfoSimple.style.background = '#fee2e2';
                portInfoSimple.style.borderColor = '#fca5a5';
                portInfoSimple.style.color = '#7f1d1d';
                portInfoSimple.querySelector('strong').style.color = '#991b1b';
            } else if (tersedia <= totalPorts * 0.2) {
                portInfoSimple.style.background = '#fef3c7';
                portInfoSimple.style.borderColor = '#fde047';
                portInfoSimple.style.color = '#713f12';
                portInfoSimple.querySelector('strong').style.color = '#854d0e';
            } else {
                portInfoSimple.style.background = '#ecfdf5';
                portInfoSimple.style.borderColor = '#a7f3d0';
                portInfoSimple.style.color = '#065f46';
                portInfoSimple.querySelector('strong').style.color = '#047857';
            }
        }
    }
}

// Fungsi untuk generate multi-port fields di modal Add OLT
function generateAddPortFields(usedPorts, totalPorts) {
    var container = document.getElementById('addPortsContainer');
    if (!container) return;
    
    // Kosongkan container
    container.innerHTML = '';
    
    if (usedPorts <= 0) {
        container.innerHTML = '<p class="text-muted" style="text-align: center; padding: 10px;"><i class="fa fa-info-circle"></i> ' + LANG.js_no_ports_used + '</p>';
        return;
    }
    
    // Header
    var headerHtml = '<div class="ports-fields-header" style="background: #f0f9ff; padding: 10px; border-radius: 6px; margin-bottom: 10px;">' +
        '<strong><i class="fa fa-plug"></i> ' + LANG.js_detail + ' ' + usedPorts + ' ' + LANG.js_ports_used + '</strong>' +
        '</div>';
    container.innerHTML = headerHtml;
    
    // Generate fields untuk setiap port terpakai
    for (var i = 0; i < usedPorts; i++) {
        var portIndex = i + 1;
        
        // Cek apakah ada data existing
        var existingPon = '';
        var existingLabel = '';
        if (window.addOltPortsData && window.addOltPortsData[i]) {
            existingPon = window.addOltPortsData[i].pon || '';
            existingLabel = window.addOltPortsData[i].label || '';
        }
        
        var fieldHtml = '<div class="port-field-item" style="background: #fafafa; padding: 12px; border-radius: 6px; margin-bottom: 10px; border: 1px solid #e5e7eb;">' +
            '<div style="font-weight: 600; margin-bottom: 8px; color: #4b5563;"><i class="fa fa-circle" style="font-size: 8px; color: #8b5cf6;"></i> ' + LANG.js_port_number + portIndex + '</div>' +
            '<div class="row">' +
                '<div class="col-md-6">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.js_pon_number_label + '</label>' +
                        '<select class="form-control input-sm" id="addPonSelect_' + i + '" onchange="onAddPonSelectChange(' + i + ')">' +
                            generateAddPonOptionsHtml(totalPorts, existingPon) +
                        '</select>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.js_port_label_label + '</label>' +
                        '<input type="text" class="form-control input-sm" id="addPortLabel_' + i + '" value="' + escapeHtml(existingLabel) + '" placeholder="' + LANG.js_port_label_placeholder + '" onchange="updateAddPortsDataFromFields()">' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        container.innerHTML += fieldHtml;
    }
    
    // Setelah semua field di-generate, update disabled state untuk semua dropdown
    updateAllAddPonDropdownsDisabledState();
}

// Fungsi untuk generate options PON di modal Add
function generateAddPonOptionsHtml(totalPorts, selectedValue) {
    var html = '<option value="">' + LANG.js_select_pon + '</option>';
    for (var i = 1; i <= totalPorts; i++) {
        var ponValue = 'PON ' + i;
        var selected = (ponValue === selectedValue) ? 'selected' : '';
        html += '<option value="' + ponValue + '" ' + selected + '>' + ponValue + '</option>';
    }
    return html;
}

// Fungsi yang dipanggil ketika PON dropdown berubah di modal Add
function onAddPonSelectChange(changedIndex) {
    // Update data dulu
    updateAddPortsDataFromFields();
    
    // Update disabled state untuk semua dropdown PON
    updateAllAddPonDropdownsDisabledState();
}

// Fungsi untuk update disabled state semua dropdown PON di modal Add
function updateAllAddPonDropdownsDisabledState() {
    var usedPorts = parseInt(document.getElementById('oltUsedPorts').value) || 0;
    
    // Kumpulkan semua PON yang sudah dipilih
    var selectedPons = [];
    for (var i = 0; i < usedPorts; i++) {
        var select = document.getElementById('addPonSelect_' + i);
        if (select && select.value) {
            selectedPons.push({
                index: i,
                value: select.value
            });
        }
    }
    
    // Update setiap dropdown
    for (var i = 0; i < usedPorts; i++) {
        var select = document.getElementById('addPonSelect_' + i);
        if (!select) continue;
        
        var currentValue = select.value;
        var options = select.querySelectorAll('option');
        
        options.forEach(function(option) {
            // Skip option default (value kosong)
            if (!option.value) {
                option.disabled = false;
                option.style.color = '';
                return;
            }
            
            // Cek apakah PON ini sudah dipilih di dropdown lain
            var isUsedByOther = selectedPons.some(function(item) {
                return item.value === option.value && item.index !== i;
            });
            
            if (isUsedByOther) {
                // Disable karena sudah dipakai dropdown lain
                option.disabled = true;
                option.style.color = '#999';
            } else {
                // Enable
                option.disabled = false;
                option.style.color = '';
            }
        });
    }
}

// Fungsi untuk update ports data dari fields ke window variable di modal Add
function updateAddPortsDataFromFields() {
    var usedPorts = parseInt(document.getElementById('oltUsedPorts').value) || 0;
    var portsData = [];
    
    for (var i = 0; i < usedPorts; i++) {
        var ponSelect = document.getElementById('addPonSelect_' + i);
        var labelInput = document.getElementById('addPortLabel_' + i);
        
        if (ponSelect && labelInput) {
            portsData.push({
                pon: ponSelect.value,
                label: labelInput.value
            });
        }
    }
    
    window.addOltPortsData = portsData;
}

// Fungsi untuk mendapatkan ports data sebagai JSON string di modal Add
function getAddPortsDataJson() {
    updateAddPortsDataFromFields();
    return JSON.stringify(window.addOltPortsData || []);
}

// Fungsi untuk reset multi-port di modal Add
function resetAddPortFields() {
    window.addOltPortsData = [];
    var container = document.getElementById('addPortsContainer');
    if (container) {
        container.innerHTML = '<p class="text-muted" style="text-align: center; padding: 10px;"><i class="fa fa-info-circle"></i> ' + LANG.js_no_ports_used + '</p>';
    }
}

// ==================== END FUNGSI MULTI-PORT ADD OLT ====================

// Fungsi untuk simpan OLT
function simpanOlt() {
    var parentType = document.getElementById('oltParentType').value;
    
    // Validasi parent connection
    if (parentType === 'router' && !document.getElementById('oltParentRouter').value) {
        Swal.fire({
            icon: 'warning',
            title: LANG.alert_data_incomplete,
            text: LANG.alert_select_router_parent,
            confirmButtonColor: '#8b5cf6'
        });
        return;
    }
    
    if (parentType === 'olt' && !document.getElementById('oltParentOlt').value) {
        Swal.fire({
            icon: 'warning',
            title: LANG.alert_data_incomplete,
            text: LANG.alert_select_olt_parent,
            confirmButtonColor: '#8b5cf6'
        });
        return;
    }
    
    // Ambil ports data dari multi-port fields
    var portsDataJson = getAddPortsDataJson();
    
    var formData = {
        name: document.getElementById('oltName').value,
        brand: document.getElementById('oltBrand').value,
        model: document.getElementById('oltModel').value,
        serial_number: document.getElementById('oltSerialNumber').value,
        ip_address: document.getElementById('oltIpAddress').value,
        coordinates: document.getElementById('oltCoordinates').value,
        address: document.getElementById('oltAddress').value,
        total_ports: document.getElementById('oltTotalPorts').value,
        used_ports: document.getElementById('oltUsedPorts').value,
        status: document.getElementById('oltStatus').value,
        description: document.getElementById('oltDescription').value,
        // Tambahan: parent connection
        parent_type: parentType,
        parent_router_id: document.getElementById('oltParentRouter').value,
        parent_olt_id: document.getElementById('oltParentOlt').value,
        // Tambahan: ports_data JSON untuk multi-port
        ports_data: portsDataJson
    };
    
    if (!formData.name) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_error,
            text: LANG.alert_olt_name_required,
            confirmButtonColor: '#8b5cf6'
        });
        return;
    }
    
    // Tampilkan loading
    Swal.fire({
        title: LANG.alert_saving,
        text: LANG.alert_please_wait,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Kirim ke server
    fetch(baseUrl + 'plugin/network_mapping/save-olt', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // PERBAIKAN: Tutup modal DULU sebelum tampilkan alert
            tutupModalOlt();
            
            // Tampilkan alert sukses
            Swal.fire({
                icon: 'success',
                title: LANG.alert_success,
                text: data.message,
                confirmButtonColor: '#8b5cf6',
                timer: 2000,
                timerProgressBar: true
            }).then(() => {
                // Reload halaman untuk refresh data
                location.reload();
            });
        } else {
            // Jika gagal, modal tetap terbuka agar user bisa koreksi
            Swal.fire({
                icon: 'error',
                title: LANG.alert_failed,
                text: data.message,
                confirmButtonColor: '#ef4444'
            });
        }
    })
    .catch(error => {
        // Jika error, modal tetap terbuka
        Swal.fire({
            icon: 'error',
            title: LANG.alert_error,
            text: LANG.alert_error_occurred + error.message,
            confirmButtonColor: '#ef4444'
        });
    });
}
// ==================== FUNGSI PARENT CONNECTION ====================

// Toggle tampilan dropdown parent berdasarkan tipe
function toggleParentSelect() {
    var parentType = document.getElementById('oltParentType').value;
    var groupRouter = document.getElementById('groupParentRouter');
    var groupOlt = document.getElementById('groupParentOlt');
    var selectOlt = document.getElementById('oltParentOlt');
    var helpNoOlt = document.getElementById('helpNoOlt');
    
    // Reset visibility
    groupRouter.style.display = 'none';
    groupOlt.style.display = 'none';
    document.getElementById('oltParentRouter').value = '';
    document.getElementById('oltParentOlt').value = '';
    
    // Reset port used jika tidak pilih parent
    if (parentType === '') {
        resetPortUsage();
        return;
    }
    
    if (parentType === 'router') {
        groupRouter.style.display = 'block';
    } else if (parentType === 'olt') {
        groupOlt.style.display = 'block';
        
        // Cek apakah ada OLT lain
        var oltOptions = selectOlt.querySelectorAll('option');
        if (oltOptions.length <= 1) {
            // Belum ada OLT lain (hanya option default)
            selectOlt.disabled = true;
            helpNoOlt.style.display = 'block';
        } else {
            selectOlt.disabled = false;
            helpNoOlt.style.display = 'none';
        }
    }
}

// Handle perubahan parent (router atau olt) - Updated untuk multi-port
function handleParentChange() {
    // Fungsi ini sekarang tidak perlu auto-set port terpakai
    // User bisa manual mengatur port terpakai dengan tombol +/-
}

// Reset port usage ke 0 - Updated untuk multi-port
function resetPortUsage() {
    var usedPortsInput = document.getElementById('oltUsedPorts');
    if (usedPortsInput) {
        usedPortsInput.value = 0;
    }
    resetAddPortFields();
    updatePortTersediaAdd();
}

// Fungsi khusus untuk button pick location di dalam modal
function aktifkanModePickDariModal() {
    console.log('🎯 Mode pick location diaktifkan dari button modal');
    
    // Tutup modal dulu
    var modal = document.getElementById('modalOltOverlay');
    if (modal) {
        modal.classList.remove('active');
    }
    
    // Aktifkan mode pick dengan tipe 'olt'
    aktifkanModePick('olt');
}

// Tutup floating menu jika klik di luar
document.addEventListener('click', function(event) {
    var fabContainer = document.querySelector('.floating-container');
    var fabMenu = document.getElementById('fabMenu');
    var fabMain = document.getElementById('fabMain');
    
    if (fabContainer && !fabContainer.contains(event.target)) {
        if (fabMenu.classList.contains('active')) {
            fabMenu.classList.remove('active');
            fabMain.classList.remove('active');
            document.getElementById('fabIcon').classList.remove('fa-times');
            document.getElementById('fabIcon').classList.add('fa-plus');
        }
    }
});

function dapatkanLokasi() {
    document.getElementById('loading').style.display = 'block';
    
    // Cek apakah ada posisi tersimpan di localStorage
    var savedPosition = localStorage.getItem('networkMapPosition');
    if (savedPosition) {
        try {
            var pos = JSON.parse(savedPosition);
            siapkanPeta(pos.lat, pos.lng, pos.zoom, pos.mode);
            return;
        } catch (e) {
            // Jika error parsing, lanjut ke geolocation
        }
    }
    
    if (window.location.protocol == "https:" && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(tampilkanPosisi, function() {
            siapkanPeta(-7.250445, 112.768845);
        });
    } else {
        siapkanPeta(-7.250445, 112.768845);
    }
}

function tampilkanPosisi(position) {
    siapkanPeta(position.coords.latitude, position.coords.longitude);
}

function siapkanPeta(lat, lon, zoom, mode) {
    // Set maxZoom di map instance agar bisa zoom sampai level 21
    // Gunakan zoom dari parameter atau default 13
    var initialZoom = zoom || 13;
    var initialMode = mode || 'jalan';
    
    map = L.map('map', {
        maxZoom: 21
    }).setView([lat, lon], initialZoom);
    
    // ==================== MODE PETA JALAN ====================
    // Layer OpenStreetMap (untuk zoom 1-15)
    var osmLayer = L.tileLayer('https://{literal}{s}{/literal}.tile.openstreetmap.org/{literal}{z}{/literal}/{literal}{x}{/literal}/{literal}{y}{/literal}.png', {
        maxNativeZoom: 19,
        maxZoom: 21,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    });
    
    // Layer Google Maps Street (untuk zoom 16-21)
    var googleStreetLayer = L.tileLayer('https://{literal}{s}{/literal}.google.com/vt/lyrs=m&hl=id&x={literal}{x}{/literal}&y={literal}{y}{/literal}&z={literal}{z}{/literal}&s=Ga', {
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        maxNativeZoom: 21,
        maxZoom: 21,
        attribution: '&copy; Google Maps'
    });
    
    // ==================== MODE SATELIT ====================
    // Layer Esri World Imagery (untuk zoom 1-15)
    var esriSatelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{literal}{z}{/literal}/{literal}{y}{/literal}/{literal}{x}{/literal}', {
        maxNativeZoom: 18,
        maxZoom: 21,
        attribution: '&copy; <a href="https://www.esri.com/">Esri</a>'
    });
    
    // Layer Google Satellite (untuk zoom 16-21)
    var googleSatelliteLayer = L.tileLayer('https://{literal}{s}{/literal}.google.com/vt/lyrs=s&hl=id&x={literal}{x}{/literal}&y={literal}{y}{/literal}&z={literal}{z}{/literal}&s=Ga', {
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        maxNativeZoom: 21,
        maxZoom: 21,
        attribution: '&copy; Google Satellite'
    });
    
    // Simpan referensi semua layer
    map.layers = {
        osm: osmLayer,
        googleStreet: googleStreetLayer,
        esriSatellite: esriSatelliteLayer,
        googleSatellite: googleSatelliteLayer
    };
    
    // Mode dan layer aktif saat ini
    map.currentMode = initialMode;
    
    // Mulai dengan layer sesuai mode dan zoom
    if (initialMode === 'satelit') {
        if (initialZoom > 19) {
            googleSatelliteLayer.addTo(map);
            map.currentLayer = 'googleSatellite';
        } else {
            esriSatelliteLayer.addTo(map);
            map.currentLayer = 'esriSatellite';
        }
        // Update tombol aktif
        document.getElementById('btnPetaJalan').classList.remove('active');
        document.getElementById('btnSatelit').classList.add('active');
    } else {
        if (initialZoom > 19) {
            googleStreetLayer.addTo(map);
            map.currentLayer = 'googleStreet';
        } else {
            osmLayer.addTo(map);
            map.currentLayer = 'osm';
        }
    }
    
    // Event listener untuk perubahan zoom
    map.on('zoomend', function() {
        aturLayerBerdasarkanZoom();
    });
    
    // Event listener untuk menyimpan posisi dan mode map ke localStorage
    map.on('moveend', function() {
        var center = map.getCenter();
        var zoom = map.getZoom();
        localStorage.setItem('networkMapPosition', JSON.stringify({
            lat: center.lat,
            lng: center.lng,
            zoom: zoom,
            mode: map.currentMode
        }));
    });
    
    // Panggil aturLayerBerdasarkanZoom() untuk set layer yang sesuai dengan zoom level awal
    setTimeout(function() {
        aturLayerBerdasarkanZoom();
    }, 100);
    
    Object.values(markersGroup).forEach(group => group.addTo(map));
    connectionsGroup.addTo(map);
    
    muatDataJaringan();
    siapkanKontrolLapisan();
    
    document.getElementById('loading').style.display = 'none';
}

// Fungsi untuk mengatur layer berdasarkan zoom level
function aturLayerBerdasarkanZoom() {
    var zoomLevel = map.getZoom();
    
    if (map.currentMode === 'jalan') {
        // Mode Peta Jalan
        if (zoomLevel > 19 && map.currentLayer !== 'googleStreet') {
            hapusSemuaLayerPeta();
            map.layers.googleStreet.addTo(map);
            map.currentLayer = 'googleStreet';
            tampilkanNotifikasiLayer('Google Maps');
        } else if (zoomLevel <= 19 && map.currentLayer !== 'osm') {
            hapusSemuaLayerPeta();
            map.layers.osm.addTo(map);
            map.currentLayer = 'osm';
            tampilkanNotifikasiLayer('OpenStreetMap');
        }
    } else {
        // Mode Satelit
        if (zoomLevel > 19 && map.currentLayer !== 'googleSatellite') {
            hapusSemuaLayerPeta();
            map.layers.googleSatellite.addTo(map);
            map.currentLayer = 'googleSatellite';
            tampilkanNotifikasiLayer('Google Satellite');
        } else if (zoomLevel <= 19 && map.currentLayer !== 'esriSatellite') {
            hapusSemuaLayerPeta();
            map.layers.esriSatellite.addTo(map);
            map.currentLayer = 'esriSatellite';
            tampilkanNotifikasiLayer('Esri Satellite');
        }
    }
    
    // Atur visibility semua kabel berdasarkan zoom
    aturVisibilityKabel();
}

// Fungsi untuk mengatur visibility SEMUA kabel berdasarkan zoom
// Semua kabel hanya tampil saat zoom >= 15
function aturVisibilityKabel() {
    var zoomLevel = map.getZoom();
    var kabelCheckbox = document.getElementById('show-kabel');
    var isKabelChecked = kabelCheckbox ? kabelCheckbox.checked : true;
    
    // Semua kabel tampil di zoom >= 13 DAN checkbox aktif
    if (zoomLevel >= 13 && isKabelChecked) {
        // Tampilkan kabel
        if (!map.hasLayer(markersGroup.kabel)) {
            map.addLayer(markersGroup.kabel);
            
            // Re-apply class blink untuk kabel offline setelah layer ditambahkan
            setTimeout(function() {
                reapplyKabelOfflineBlink();
            }, 100);
        }
        // Tampilkan sambungan di zoom >= 16
        if (zoomLevel >= 16 && !map.hasLayer(markersGroup.sambunganKabel)) {
            map.addLayer(markersGroup.sambunganKabel);
        } else if (zoomLevel < 16 && map.hasLayer(markersGroup.sambunganKabel)) {
            map.removeLayer(markersGroup.sambunganKabel);
        }
        
        // Handle animasi flow berdasarkan zoom
        updateKabelFlowAnimation(zoomLevel > 19);
    } else {
        // Sembunyikan kabel
        if (map.hasLayer(markersGroup.kabel)) {
            map.removeLayer(markersGroup.kabel);
        }
        // Sembunyikan sambungan juga
        if (map.hasLayer(markersGroup.sambunganKabel)) {
            map.removeLayer(markersGroup.sambunganKabel);
        }
    }
}

// Fungsi untuk menghapus semua layer peta
function hapusSemuaLayerPeta() {
    Object.values(map.layers).forEach(function(layer) {
        if (map.hasLayer(layer)) {
            map.removeLayer(layer);
        }
    });
}

// Fungsi untuk ganti mode peta (Jalan / Satelit)
function gantiModePeta(mode) {
    var btnJalan = document.getElementById('btnPetaJalan');
    var btnSatelit = document.getElementById('btnSatelit');
    var mapTypeFab = document.getElementById('mapTypeFab');
    var mapTypeFabIcon = document.getElementById('mapTypeFabIcon');
    var zoomLevel = map.getZoom();
    
    // Update tombol aktif
    if (mode === 'jalan') {
        btnJalan.classList.add('active');
        btnSatelit.classList.remove('active');
    } else {
        btnSatelit.classList.add('active');
        btnJalan.classList.remove('active');
    }
    
    // Tutup menu setelah pilih
    tutupMapTypeMenu();
    
    // Set mode baru
    map.currentMode = mode;
    
    // Simpan mode ke localStorage
    var center = map.getCenter();
    var zoom = map.getZoom();
    localStorage.setItem('networkMapPosition', JSON.stringify({
        lat: center.lat,
        lng: center.lng,
        zoom: zoom,
        mode: mode
    }));
    
    // Hapus semua layer peta
    hapusSemuaLayerPeta();
    
    // Tambahkan layer sesuai mode dan zoom
    if (mode === 'jalan') {
        if (zoomLevel >= 16) {
            map.layers.googleStreet.addTo(map);
            map.currentLayer = 'googleStreet';
            tampilkanNotifikasiLayer('Google Maps');
        } else {
            map.layers.osm.addTo(map);
            map.currentLayer = 'osm';
            tampilkanNotifikasiLayer('OpenStreetMap');
        }
    } else {
        if (zoomLevel >= 16) {
            map.layers.googleSatellite.addTo(map);
            map.currentLayer = 'googleSatellite';
            tampilkanNotifikasiLayer('Google Satellite');
        } else {
            map.layers.esriSatellite.addTo(map);
            map.currentLayer = 'esriSatellite';
            tampilkanNotifikasiLayer('Esri Satellite');
        }
    }
}

// Fungsi untuk toggle menu map type
function toggleMapTypeMenu() {
    var mapTypeFab = document.getElementById('mapTypeFab');
    var mapTypeMenu = document.getElementById('mapTypeMenu');
    
    mapTypeFab.classList.toggle('active');
    mapTypeMenu.classList.toggle('active');
}

// Fungsi untuk tutup menu map type
function tutupMapTypeMenu() {
    var mapTypeFab = document.getElementById('mapTypeFab');
    var mapTypeMenu = document.getElementById('mapTypeMenu');
    
    mapTypeFab.classList.remove('active');
    mapTypeMenu.classList.remove('active');
}

// Tutup map type menu jika klik di luar
document.addEventListener('click', function(event) {
    var mapTypeContainer = document.querySelector('.map-type-container');
    var mapTypeMenu = document.getElementById('mapTypeMenu');
    
    if (mapTypeContainer && !mapTypeContainer.contains(event.target)) {
        if (mapTypeMenu && mapTypeMenu.classList.contains('active')) {
            tutupMapTypeMenu();
        }
    }
});

// Fungsi untuk menampilkan notifikasi pergantian layer
function tampilkanNotifikasiLayer(namaLayer) {
    // Hapus notifikasi lama jika ada
    var notifLama = document.querySelector('.layer-notif');
    if (notifLama) {
        notifLama.remove();
    }
    
    // Buat notifikasi baru
    var notif = document.createElement('div');
    notif.className = 'layer-notif';
    notif.innerHTML = '<i class="fa fa-map"></i> ' + namaLayer;
    document.getElementById('map').appendChild(notif);
    
    // Hilangkan setelah 2 detik
    setTimeout(function() {
        notif.classList.add('fade-out');
        setTimeout(function() {
            notif.remove();
        }, 300);
    }, 2000);
}

// Fungsi untuk menentukan warna ping berdasarkan nilai (GLOBAL SCOPE)
// Support format MikroTik ROS7: "13ms879us", "1.299", "13ms", "0.5ms", "304us", dll
function getPingColorClass(pingValue) {
    if (!pingValue || pingValue === 'Timeout' || pingValue === '-' || pingValue === 'timeout') {
        return 'realtime-ping-bad';
    }
    
    var pingMs = 0;
    var pingStr = String(pingValue).toLowerCase().trim();
    
    // Format 1: "13ms879us" atau "13ms" (ada 'ms' di string)
    if (pingStr.indexOf('ms') !== -1) {
        // Ambil bagian sebelum 'ms' pertama
        var msPart = pingStr.split('ms')[0];
        // Parse sebagai float untuk handle "0.5ms"
        pingMs = parseFloat(msPart);
    }
    // Format 2: "304us" (hanya microseconds, tanpa ms)
    else if (pingStr.indexOf('us') !== -1) {
        // Ambil bagian sebelum 'us' dan konversi ke ms (bagi 1000)
        var usPart = pingStr.split('us')[0];
        var usValue = parseFloat(usPart);
        pingMs = usValue / 1000; // Konversi microseconds ke milliseconds
    }
    // Format 3: "1.299" atau "13" (angka murni, diasumsikan dalam ms)
    else {
        pingMs = parseFloat(pingStr);
    }
    
    // Validasi hasil parsing
    if (isNaN(pingMs)) {
        return 'realtime-ping-bad';
    }
    
    // Klasifikasi warna berdasarkan nilai ping
    if (pingMs < 30) {
        return 'realtime-ping-good';      // Hijau: < 30ms
    } else if (pingMs <= 100) {
        return 'realtime-ping-medium';    // Kuning: 30-100ms
    } else {
        return 'realtime-ping-bad';       // Merah: > 100ms
    }
}

// Fungsi untuk menentukan warna RX Power berdasarkan nilai (GLOBAL SCOPE)
// > -20 dBm = Hijau, -20 s/d -25 dBm = Kuning, < -25 dBm = Merah
function getRxPowerColorClass(rxValue) {
    if (!rxValue || rxValue === '-' || rxValue === '') {
        return 'realtime-ping-bad';
    }
    
    // Parse nilai RX Power (hapus dBm jika ada, ambil angka saja)
    var rxStr = String(rxValue).toLowerCase().replace('dbm', '').trim();
    var rxNum = parseFloat(rxStr);
    
    // Validasi hasil parsing
    if (isNaN(rxNum)) {
        return 'realtime-ping-bad';
    }
    
    // Klasifikasi warna berdasarkan nilai RX Power
    if (rxNum > -20) {
        return 'realtime-ping-good';      // Hijau: > -20 dBm
    } else if (rxNum >= -25) {
        return 'realtime-ping-medium';    // Kuning: -20 s/d -25 dBm
    } else {
        return 'realtime-ping-bad';       // Merah: < -25 dBm
    }
}

function muatDataJaringan() {
    Object.values(markersGroup).forEach(group => group.clearLayers());
    connectionsGroup.clearLayers();
    
    var semuaKoordinat = [];
    
    // Tambah router
    if (networkData.routers) {
        networkData.routers.forEach(function(router) {
            var coords = router.coordinates.split(',').map(parseFloat);
            semuaKoordinat.push(coords);
            
            // Warna marker berdasarkan status
            var markerClass = 'router-marker';
            if (router.enabled != 1) {
                markerClass = 'router-marker router-marker-inactive';
            }
            
            var routerIcon = L.divIcon({
                className: 'custom-div-icon',
                html: '<div class="' + markerClass + '"><i class="fa fa-server"></i></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });
            
            var marker = L.marker(coords, { icon: routerIcon }).addTo(markersGroup.routers);
            marker.bindPopup(router.popup_content);
            
            var statusText = router.enabled == 1 ? LANG.status_online : LANG.status_offline;
            marker.bindTooltip(router.name + ' (' + statusText + ')', { permanent: false, direction: 'top' });
            
            if (router.coverage > 0) {
                var circle = L.circle(coords, {
                    radius: router.coverage,
                    color: router.enabled == 1 ? 'blue' : 'red',
                    fillOpacity: 0.1,
                    weight: 2,
                    interactive: false
                }).addTo(markersGroup.routers);
            }
        });
    }
    
    // Tambah OLT
    if (networkData.olts) {
        networkData.olts.forEach(function(olt) {
            var coords = olt.coordinates.split(',').map(parseFloat);
            semuaKoordinat.push(coords);
            
            // Tentukan class CSS berdasarkan status
            var oltMarkerClass = 'olt-marker';
            if (olt.status === 'Maintenance') {
                oltMarkerClass = 'olt-marker olt-marker-maintenance';
            } else if (olt.status === 'Inactive') {
                oltMarkerClass = 'olt-marker olt-marker-inactive';
            }
            
            var oltIcon = L.divIcon({
                className: 'custom-div-icon',
                html: '<div class="' + oltMarkerClass + '"><i class="fa fa-hdd-o"></i></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });
            
            var marker = L.marker(coords, { icon: oltIcon }).addTo(markersGroup.olts);
            marker.bindPopup(olt.popup_content);
            marker.bindTooltip(olt.name + ' (' + olt.available_ports + ' port)', { permanent: false, direction: 'top' });
        });
    }
    
    // Tambah ODC
    // Render ODC markers (1 marker per koordinat/box)
    if (networkData.odcs) {
        networkData.odcs.forEach(function(odc) {
            var coords = odc.coordinates.split(',').map(parseFloat);
            semuaKoordinat.push(coords);
            
            // Tampilkan badge jumlah slot jika > 1
            var slotBadge = '';
            if (odc.total_slots && odc.total_slots > 1) {
                slotBadge = '<span class="odc-slot-badge">' + odc.total_slots + '</span>';
            }
            
            // Tentukan class marker berdasarkan status (hanya untuk single slot)
            var odcMarkerClass = 'odc-marker';
            if (odc.total_slots == 1 && odc.single_slot_status) {
                if (odc.single_slot_status === 'Maintenance') {
                    odcMarkerClass = 'odc-marker odc-marker-maintenance';
                } else if (odc.single_slot_status === 'Inactive') {
                    odcMarkerClass = 'odc-marker odc-marker-inactive';
                }
            }
            
            var odcIcon = L.divIcon({
                className: 'custom-div-icon',
                html: '<div class="' + odcMarkerClass + '">' + slotBadge + '<i class="fa fa-cube"></i></div>',
                iconSize: [18, 18],
                iconAnchor: [9, 9]
            });
            
            var marker = L.marker(coords, { icon: odcIcon });
            marker.bindPopup(odc.popup_content, { maxWidth: 350 });
            markersGroup.odcs.addLayer(marker);
            
            // Tooltip dengan info slot
            var tooltipText = odc.name;
            if (odc.total_slots && odc.total_slots > 1) {
                tooltipText += ' (' + odc.total_slots + ' slot, ' + odc.grand_available_ports + ' port)';
            } else {
                tooltipText += ' (' + (odc.grand_available_ports || odc.available_ports) + ' port)';
            }
            marker.bindTooltip(tooltipText, { permanent: false, direction: 'top' });
        });
    }
    
    // Render ODP markers (1 marker per koordinat/box)
    if (networkData.odps) {
        networkData.odps.forEach(function(odp) {
            var coords = odp.coordinates.split(',').map(parseFloat);
            semuaKoordinat.push(coords);
            
            // Tampilkan badge jumlah slot jika > 1
            var slotBadge = '';
            if (odp.total_slots && odp.total_slots > 1) {
                slotBadge = '<span class="odp-slot-badge">' + odp.total_slots + '</span>';
            }
            
            // Tentukan class marker berdasarkan status (hanya untuk single slot)
            var odpMarkerClass = 'odp-marker';
            if (odp.total_slots == 1 && odp.single_slot_status) {
                if (odp.single_slot_status === 'Maintenance') {
                    odpMarkerClass = 'odp-marker odp-marker-maintenance';
                } else if (odp.single_slot_status === 'Inactive') {
                    odpMarkerClass = 'odp-marker odp-marker-inactive';
                }
            }
            
            var odpIcon = L.divIcon({
                className: 'custom-div-icon',
                html: '<div class="' + odpMarkerClass + '">' + slotBadge + '<i class="fa fa-circle-o"></i></div>',
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            });
            
            var marker = L.marker(coords, { icon: odpIcon });
            marker.bindPopup(odp.popup_content, { maxWidth: 350 });
            markersGroup.odps.addLayer(marker);
            
            // Tooltip dengan info slot
            var tooltipText = odp.name;
            if (odp.total_slots && odp.total_slots > 1) {
                tooltipText += ' (' + odp.total_slots + ' slot, ' + odp.grand_available_ports + ' port)';
            } else {
                tooltipText += ' (' + (odp.grand_available_ports || odp.available_ports) + ' port)';
            }
            marker.bindTooltip(tooltipText, { permanent: false, direction: 'top' });
        });
    }
    
    // Tambah Tiang
    if (networkData.tiang) {
        networkData.tiang.forEach(function(tiang) {
            var coords = tiang.coordinates.split(',').map(parseFloat);
            semuaKoordinat.push(coords);
            
            // Tentukan class berdasarkan status
            var markerClass = 'tiang-marker';
            if (tiang.status === 'Perbaikan') {
                markerClass = 'tiang-marker tiang-marker-perbaikan';
            } else if (tiang.status === 'Rusak') {
                markerClass = 'tiang-marker tiang-marker-rusak';
            }
            
            var tiangIcon = L.divIcon({
                className: 'custom-div-icon',
                html: '<div class="' + markerClass + '"><i class="fa fa-minus"></i></div>',
                iconSize: [18, 32],
                iconAnchor: [9, 32]
            });
            
            var marker = L.marker(coords, { icon: tiangIcon }).addTo(markersGroup.tiang);
            marker.bindPopup(tiang.popup_content);
            
            var tooltipText = tiang.name;
            if (tiang.slack_kabel) {
                tooltipText += ' (Slack: ' + formatMeter(tiang.slack_kabel) + ')';
            }
            marker.bindTooltip(tooltipText, { permanent: false, direction: 'top' });
        });
    }
    
    // Tambah Homepass dengan clustering
    if (networkData.homepass) {
        networkData.homepass.forEach(function(hp) {
            var coords = hp.coordinates.split(',').map(parseFloat);
            semuaKoordinat.push(coords);
            
            var markerColor = hp.status_color || '#ec4899';
            
            var homepassIcon = L.divIcon({
                className: 'custom-div-icon homepass-icon',
                html: '<div class="homepass-marker" style="background-color: ' + markerColor + ';"><i class="fa fa-home"></i></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });
            
            var marker = L.marker(coords, { 
                icon: homepassIcon,
                homepassId: hp.id,
                homepassData: hp
            });
            marker.bindPopup(hp.popup_content);
            {literal}
            var hpStatusMap = {'Prospek': LANG.homepass_status_prospek, 'Bermasalah': LANG.homepass_status_bermasalah, 'Tidak Minat': LANG.homepass_status_tidak_minat, 'Rumah Kosong': LANG.homepass_status_rumah_kosong, 'Sudah Langganan': LANG.homepass_status_sudah_langganan, 'Pending': LANG.homepass_status_pending};
            {/literal}
            var hpStatusText = hpStatusMap[hp.status] || hp.status;
            marker.bindTooltip(hp.name + ' (' + hpStatusText + ')', { permanent: false, direction: 'top' });
            markersGroup.homepass.addLayer(marker);
        });
    }
    
    // Fungsi untuk generate popup content customer berdasarkan status
    function generateCustomerPopupContent(customer) {
        /*
         * 4 Status dari database (online_status):
         * 1. online    = Aktif, ada di active connection, marker cyan
         * 2. isolir    = Isolir tapi masih konek, marker pink-ungu
         * 3. offline   = Offline total, marker merah
         * 4. off_isolir = Offline + Isolir, marker merah + badge isolir
         */
        var status = customer.online_status || 'offline';
        var isOffline = (status === 'offline' || status === 'off_isolir');
        var isIsolir = (status === 'isolir' || status === 'off_isolir');
        var isIsolirConnected = (status === 'isolir'); // Isolir tapi masih konek
        
        // Header dan button class
        var headerClass = 'popup-header popup-header-customer';
        var btnClass = 'popup-btn popup-btn-customer';
        if (isOffline) {
            headerClass = 'popup-header popup-header-customer-offline';
            btnClass = 'popup-btn popup-btn-customer-offline';
        } else if (isIsolirConnected) {
            headerClass = 'popup-header popup-header-customer-isolir';
            btnClass = 'popup-btn popup-btn-customer-isolir';
        }
        
        // Status badge class dan text
        var statusBadgeClass = 'popup-status-badge popup-status-online';
        var statusText = 'Online';
        if (status === 'offline') {
            statusBadgeClass = 'popup-status-badge popup-status-offline';
            statusText = 'Offline';
        } else if (status === 'isolir') {
            statusBadgeClass = 'popup-status-badge popup-status-isolir-connected';
            statusText = 'Isolir';
        } else if (status === 'off_isolir') {
            statusBadgeClass = 'popup-status-badge popup-status-offline';
            statusText = 'Offline';
        }
        
        // Default realtime values - hanya tampilkan jika online atau isolir (masih konek)
        var showRealtime = (status === 'online' || status === 'isolir');
        var uploadSpeed = showRealtime ? (customer.realtimeUpload || '0 bps') : '-';
        var downloadSpeed = showRealtime ? (customer.realtimeDownload || '0 bps') : '-';
        var pingTime = showRealtime ? (customer.realtimePing || '-') : '-';
        var pingClass = showRealtime ? getPingColorClass(pingTime) : '';
        var uptimeValue = showRealtime ? (customer.realtimeUptime || '-') : '-';
        
        // IP Address: prioritas current_ip dari MikroTik, lalu pppoe_ip dari database
        var ipAddress = customer.current_ip || customer.realtimeIp || customer.pppoe_ip || '-';
        if (!showRealtime && !customer.current_ip) {
            ipAddress = '-';
        }
        
        // PPPoE Username dari database
        var pppoeUsername = customer.pppoe_username || customer.username || '-';
        
        // ODP Name dari database
        var odpName = customer.odp_name || '-';
        
        // RX Power dari database
        var rxPower = customer.rx_power || '-';
        var rxPowerClass = getRxPowerColorClass(rxPower);
        var rxPowerDisplay = rxPower === 'LOCKED'
            ? "<a href='https://t.me/Exoforb' target='_blank' style='text-decoration: none;'><span class='popup-status-badge popup-status-offline'><i class='fa fa-lock'></i> " + LANG.rx_power_locked + "</span></a>"
            : (rxPower !== '-' && rxPower !== '' && rxPower !== 'N/A' ? rxPower + ' dBm' : LANG.rx_power_na);
        
        // Badge isolir (untuk off_isolir)
        var isolirBadge = '';
        if (status === 'off_isolir') {
            isolirBadge = " <span class='popup-status-badge popup-status-isolir'>" + LANG.status_isolir + "</span>";
        }
        
        // Foto lokasi HTML - dengan status untuk warna header fullscreen
        // Gunakan event.stopPropagation() untuk mencegah popup tertutup saat klik foto di mobile
        var fotoLokasiHtml = '';
        if (customer.foto_lokasi_url) {
            fotoLokasiHtml = "<div class='popup-foto-container'><img src='" + customer.foto_lokasi_url + "' alt='" + LANG.form_location_photo + " " + customer.name + "' class='popup-foto' onclick='event.stopPropagation(); lihatFotoFullscreen(\"" + customer.foto_lokasi_url + "\", \"" + customer.name + "\", \"customer\", \"" + status + "\")'></div>";
        }
        
        var popupContent = 
            "<div class='" + headerClass + "'>" +
                "<i class='fa fa-user'></i> <span class='popup-header-text'>" + customer.name + "</span>" +
            "</div>" +
            "<div class='popup-body'>" +
                // Foto Lokasi (jika ada)
                fotoLokasiHtml +
                // Kategori: Status & Signal
                "<div class='popup-category'><i class='fa fa-wifi'></i> " + LANG.popup_status_signal + "</div>" +
                "<div class='popup-info-row'><i class='fa fa-info-circle'></i> <span class='" + statusBadgeClass + "'>" + statusText + "</span>" + isolirBadge + "</div>" +
                "<div class='popup-info-row'><i class='fa fa-signal " + rxPowerClass + "'></i> <span class='" + rxPowerClass + "'>RX: <strong>" + rxPowerDisplay + "</strong></span></div>" +
                // Kategori: Monitoring
                "<div class='popup-category'><i class='fa fa-dashboard'></i> " + LANG.popup_monitoring + "</div>" +
                "<div class='popup-info-row'><i class='fa fa-clock-o text-info'></i> <span>" + LANG.popup_uptime + ": <strong id='realtime-uptime-" + customer.id + "'>" + uptimeValue + "</strong></span></div>" +
                "<div class='popup-info-row'><i class='fa fa-arrow-up text-success'></i> <span id='realtime-upload-" + customer.id + "' class='realtime-upload'>" + uploadSpeed + "</span></div>" +
                "<div class='popup-info-row'><i class='fa fa-arrow-down text-primary'></i> <span id='realtime-download-" + customer.id + "' class='realtime-download'>" + downloadSpeed + "</span></div>" +
                "<div class='popup-info-row'><i class='fa fa-exchange " + pingClass + "'></i> <span id='realtime-ping-" + customer.id + "' class='" + pingClass + "'>" + pingTime + "</span></div>" +
                // Kategori: Info Layanan
                "<div class='popup-category'><i class='fa fa-cogs'></i> " + LANG.popup_service_info + "</div>" +
                "<div class='popup-info-row'><i class='fa fa-cube'></i> <span>" + LANG.popup_package + ": <strong>" + (customer.nama_paket || '-') + "</strong></span></div>" +
                "<div class='popup-info-row'><i class='fa fa-plug'></i> <span>" + LANG.popup_pppoe + ": <strong>" + pppoeUsername + "</strong></span></div>" +
                "<div class='popup-info-row'><i class='fa fa-globe'></i> <span>IP: <strong id='realtime-ip-" + customer.id + "'>" + ipAddress + "</strong></span></div>" +
                "<div class='popup-info-row'><i class='fa fa-circle-o'></i> <span>" + LANG.odp + ": <strong>" + odpName + "</strong></span></div>" +
                // Kategori: Info Pelanggan
                "<div class='popup-category'><i class='fa fa-user'></i> " + LANG.popup_customer_info + "</div>" +
                "<div class='popup-info-row'><i class='fa fa-money'></i> <span>" + LANG.popup_balance + ": <strong>" + customer.balance_formatted + "</strong></span></div>" +
                (customer.address ? "<div class='popup-info-row'><i class='fa fa-map-marker'></i> <span class='popup-text-wrap'>" + customer.address + "</span></div>" : "") +
            "</div>" +
            "<div class='popup-footer'>" +
                "<a href='" + customer.detail_url + "' class='" + btnClass + "'><i class='fa fa-eye'></i> " + LANG.js_detail + "</a>" +
                "<a href='" + customer.maps_url + "' target='_blank' class='popup-btn popup-btn-secondary'><i class='fa fa-location-arrow'></i> " + LANG.popup_route + "</a>" +
            "</div>";
        
        return popupContent;
    }

    // Tambah pelanggan dengan clustering
    if (networkData.customers) {
        networkData.customers.forEach(function(customer) {
            var coords = customer.coordinates.split(',').map(parseFloat);
            semuaKoordinat.push(coords);
            
            /*
             * Status dari database (online_status):
             * 1. online    = Marker cyan (default)
             * 2. isolir    = Marker pink-ungu
             * 3. offline   = Marker merah
             * 4. off_isolir = Marker merah
             */
            var status = customer.online_status || 'offline';
            var markerClass = 'customer-marker'; // default cyan
            
            if (status === 'offline' || status === 'off_isolir') {
                markerClass = 'customer-marker-offline'; // merah
            } else if (status === 'isolir') {
                markerClass = 'customer-marker-isolir'; // pink-ungu
            }
            
            var customerIcon = L.divIcon({
                className: 'custom-div-icon',
                html: '<div class="' + markerClass + '" data-customer-id="' + customer.id + '"><i class="fa fa-user"></i></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });
            
            var marker = L.marker(coords, { 
                icon: customerIcon,
                customerId: customer.id,
                customerData: customer
            });
            
            // Bind popup dengan fungsi dinamis (update setiap kali dibuka)
            marker.on('click', function() {
                this.setPopupContent(generateCustomerPopupContent(customer));
            });
            marker.bindPopup(generateCustomerPopupContent(customer));
            marker.bindTooltip(customer.name, { permanent: false, direction: 'top' });
            
            // Event saat popup dibuka - start realtime monitoring (hanya jika online atau isolir)
            marker.on('popupopen', function() {
                if (status === 'online' || status === 'isolir') {
                    startRealtimeMonitoring(customer);
                }
            });
            
            // Event saat popup ditutup - stop realtime monitoring
            marker.on('popupclose', function() {
                stopRealtimeMonitoring(customer);
            });
            
            // Simpan referensi marker di customer data
            customer.marker = marker;
            
            markersGroup.customers.addLayer(marker);
        });
        
        // Update counter online/offline/isolir
        setTimeout(function() {
            updateOnlineOfflineCounter();
        }, 500);
        
        // Hitung dan update counter router aktif/nonaktif
        setTimeout(function() {
            updateRouterCounter();
        }, 500);
    }
    
    // Fungsi untuk menghitung posisi koordinat pada polyline berdasarkan jarak
    function getPointAtDistance(coordinates, distanceMeters, fromEnd) {
        if (!coordinates || coordinates.length < 2) return null;
        
        // Jika fromEnd true, balik koordinat
        var coords = fromEnd ? coordinates.slice().reverse() : coordinates;
        
        var totalDistance = 0;
        var targetDistance = distanceMeters;
        
        for (var i = 0; i < coords.length - 1; i++) {
            var start = L.latLng(coords[i][0], coords[i][1]);
            var end = L.latLng(coords[i + 1][0], coords[i + 1][1]);
            var segmentDistance = start.distanceTo(end);
            
            if (totalDistance + segmentDistance >= targetDistance) {
                // Titik berada di segment ini
                var remainingDistance = targetDistance - totalDistance;
                var ratio = remainingDistance / segmentDistance;
                
                var lat = coords[i][0] + (coords[i + 1][0] - coords[i][0]) * ratio;
                var lng = coords[i][1] + (coords[i + 1][1] - coords[i][1]) * ratio;
                
                return [lat, lng];
            }
            
            totalDistance += segmentDistance;
        }
        
        // Jika jarak melebihi panjang kabel, return titik terakhir
        return coords[coords.length - 1];
    }
    
    // Fungsi untuk render marker sambungan pada kabel
    function renderSambunganMarkers(kabel) {
        if (!kabel.sambungan_data || kabel.sambungan_data.length === 0) return;
        if (!kabel.coordinates_path || kabel.coordinates_path.length < 2) return;
        
        kabel.sambunganMarkers = [];
        
        kabel.sambungan_data.forEach(function(sambungan, idx) {
            var fromEnd = (sambungan.titik === 'device_2');
            var position = getPointAtDistance(kabel.coordinates_path, sambungan.jarak, fromEnd);
            
            if (position) {
                var tooltipText = LANG.popup_connection + ' #' + (idx + 1) + ' (' + sambungan.jarak + 'm)';
                if (sambungan.keterangan) {
                    tooltipText += ' - ' + sambungan.keterangan;
                }
                
                // Icon tetap sama (orange) baik ada foto atau tidak
                var markerHtml = '<div class="sambungan-marker"><i class="fa fa-circle"></i></div>';
                
                var sambunganIcon = L.divIcon({
                    className: 'sambungan-marker-container',
                    html: markerHtml,
                    iconSize: [12, 12],
                    iconAnchor: [6, 6]
                });
                
                var marker = L.marker(position, { 
                    icon: sambunganIcon,
                    interactive: true,
                    zIndexOffset: 1000
                });
                
                // Simpan data untuk popup
                marker._sambunganData = {
                    kabel_id: kabel.id,
                    kabel_name: kabel.name,
                    sambungan_index: idx + 1,
                    jarak: sambungan.jarak,
                    keterangan: sambungan.keterangan,
                    titik: sambungan.titik,
                    foto: sambungan.foto,
                    foto_url: sambungan.foto_url,
                    device_1_name: kabel.device_1_name,
                    device_2_name: kabel.device_2_name,
                    coordinates: position[0] + ',' + position[1]
                };
                
                marker.bindTooltip(tooltipText, { permanent: false, direction: 'top' });
                
                // Klik untuk buka popup foto
                marker.on('click', function(e) {
                    tampilkanPopupFotoSambungan(this._sambunganData);
                });
                
                marker.addTo(markersGroup.sambunganKabel);
                
                kabel.sambunganMarkers.push(marker);
            }
        });
    }

    // Fungsi untuk handle klik kabel (deteksi kabel bertumpuk)
    function handleKabelClick(e) {
        // Jika sedang mode kabel, abaikan
        if (isKabelMode) return;
        
        var clickLatLng = e.latlng;
        var clickedKabels = [];
        var tolerance = 0.0001; // Toleransi untuk deteksi kabel bertumpuk
        
        // Cari semua kabel yang dekat dengan titik klik
        networkData.kabel.forEach(function(kabel) {
            if (kabel.hitboxPolyline) {
                var closestPoint = kabel.hitboxPolyline.closestLayerPoint(map.latLngToLayerPoint(clickLatLng));
                if (closestPoint && closestPoint.distance < 15) {
                    clickedKabels.push(kabel);
                }
            }
        });
        
        if (clickedKabels.length === 0) {
            return;
        } else if (clickedKabels.length === 1) {
            // Hanya 1 kabel, langsung buka popup
            var kabel = clickedKabels[0];
            L.popup()
                .setLatLng(clickLatLng)
                .setContent(kabel.popup_content)
                .openOn(map);
        } else {
            // Multiple kabel, tampilkan popup pilihan
            showKabelSelectionPopup(clickLatLng, clickedKabels);
        }
    }
    
    // Fungsi untuk menampilkan popup pilihan kabel
    function showKabelSelectionPopup(latlng, kabels) {
        var html = '<div class="kabel-selection-popup">';
        html += '<div class="kabel-selection-header"><i class="fa fa-list"></i> ' + LANG.popup_select_cable + ' (' + kabels.length + ')</div>';
        html += '<div class="kabel-selection-list">';
        
        kabels.forEach(function(kabel, index) {
            var kabelName = kabel.name || 'Kabel #' + kabel.id;
            var kabelPanjang = formatMeter(kabel.panjang);
            var statusClass = '';
            var statusText = '';
            
            // Cek status kabel (jika terhubung customer)
            if (kabel.connectedCustomerId) {
                var customer = networkData.customers.find(function(c) {
                    return c.id == kabel.connectedCustomerId;
                });
                if (customer) {
                    var status = customer.online_status || 'offline';
                    if (status === 'online') {
                        statusClass = 'kabel-status-online';
                        statusText = ' <span class="kabel-badge-online">' + LANG.status_online + '</span>';
                    } else if (status === 'isolir') {
                        statusClass = 'kabel-status-isolir';
                        statusText = ' <span class="kabel-badge-isolir">' + LANG.status_isolir + '</span>';
                    } else {
                        statusClass = 'kabel-status-offline';
                        statusText = ' <span class="kabel-badge-offline">' + LANG.status_offline + '</span>';
                    }
                }
            }
            
            html += '<div class="kabel-selection-item ' + statusClass + '" onclick="selectKabelFromPopup(' + index + ')" data-kabel-index="' + index + '">';
            html += '<i class="fa fa-minus"></i> ';
            html += '<span class="kabel-selection-name">' + kabelName + '</span>';
            html += '<span class="kabel-selection-length">(' + kabelPanjang + ')</span>';
            html += statusText;
            html += '</div>';
        });
        
        html += '</div></div>';
        
        // Simpan kabels untuk diakses saat dipilih
        window._pendingKabelSelection = {
            latlng: latlng,
            kabels: kabels
        };
        
        L.popup({
            maxWidth: 300,
            className: 'kabel-selection-popup-container'
        })
            .setLatLng(latlng)
            .setContent(html)
            .openOn(map);
    }
    
    // Fungsi untuk memilih kabel dari popup pilihan
    window.selectKabelFromPopup = function(index) {
        if (window._pendingKabelSelection) {
            var kabel = window._pendingKabelSelection.kabels[index];
            var latlng = window._pendingKabelSelection.latlng;
            
            // Tutup popup pilihan dan buka popup kabel
            map.closePopup();
            
            setTimeout(function() {
                L.popup()
                    .setLatLng(latlng)
                    .setContent(kabel.popup_content)
                    .openOn(map);
            }, 100);
            
            delete window._pendingKabelSelection;
        }
    };

    // Tambah Kabel (Polylines)
    if (networkData.kabel) {
        networkData.kabel.forEach(function(kabel) {
            if (kabel.coordinates_path && kabel.coordinates_path.length >= 2) {
                // Hitbox polyline (invisible, lebih tebal untuk area klik)
                var hitboxPolyline = L.polyline(kabel.coordinates_path, {
                    color: 'transparent',
                    weight: 20,
                    opacity: 0
                }).addTo(markersGroup.kabel);
                
                // Polyline visual (yang terlihat)
                var polyline = L.polyline(kabel.coordinates_path, {
                    color: kabel.cable_color || '#78716c',
                    weight: 4,
                    opacity: 0.8,
                    interactive: false
                }).addTo(markersGroup.kabel);
                
                // Simpan data kabel ke hitbox untuk popup pilihan
                hitboxPolyline.kabelData = kabel;
                
                // Event klik untuk handle kabel bertumpuk
                hitboxPolyline.on('click', function(e) {
                    handleKabelClick(e);
                });
                
                var tooltipText = (kabel.name || LANG.kabel_default_name + kabel.id) + ' (' + formatMeter(kabel.panjang) + ')';
                hitboxPolyline.bindTooltip(tooltipText, { permanent: false, direction: 'center' });
                
                // Simpan referensi polyline dan cek apakah terhubung ke customer
                kabel.polyline = polyline;
                kabel.hitboxPolyline = hitboxPolyline;
                kabel.connectedCustomerId = null;
                
                if (kabel.device_1_type === 'customer') {
                    kabel.connectedCustomerId = kabel.device_1_id;
                } else if (kabel.device_2_type === 'customer') {
                    kabel.connectedCustomerId = kabel.device_2_id;
                }
                
                // Render marker sambungan pada kabel
                renderSambunganMarkers(kabel);
            }
        });
    }
    
    // Update warna kabel berdasarkan status customer
    setTimeout(function() {
        updateKabelCustomerStatus();
        // Update animasi flow setelah status kabel di-update
        updateKabelFlowAnimation(map.getZoom() > 19);
    }, 100);
    
    // Atur visibility kabel berdasarkan zoom awal
    aturVisibilityKabel();
    
    // Hanya fitBounds jika TIDAK ada posisi tersimpan di localStorage
    var savedPosition = localStorage.getItem('networkMapPosition');
    if (!savedPosition && semuaKoordinat.length > 0) {
        var allMarkers = [];
        Object.values(markersGroup).forEach(function(group) {
            if (group.getLayers) {
                allMarkers = allMarkers.concat(group.getLayers());
            }
        });
        if (allMarkers.length > 0) {
            var featureGroup = new L.featureGroup(allMarkers);
            if (featureGroup.getBounds().isValid()) {
                map.fitBounds(featureGroup.getBounds(), { padding: [20, 20] });
            }
        }
    }
}

function siapkanKontrolLapisan() {
    var tampilRouter = document.getElementById('show-routers');
    var tampilOlt = document.getElementById('show-olts');
    var tampilOdc = document.getElementById('show-odcs');
    var tampilOdp = document.getElementById('show-odps');
    var tampilTiang = document.getElementById('show-tiang');
    var tampilPelanggan = document.getElementById('show-customers');
    
    if (tampilRouter) {
        tampilRouter.addEventListener('change', function() {
            if (this.checked) {
                map.addLayer(markersGroup.routers);
            } else {
                map.removeLayer(markersGroup.routers);
            }
        });
    }
    
    if (tampilOlt) {
        tampilOlt.addEventListener('change', function() {
            if (this.checked) {
                map.addLayer(markersGroup.olts);
            } else {
                map.removeLayer(markersGroup.olts);
            }
        });
    }
    
    if (tampilOdc) {
        tampilOdc.addEventListener('change', function() {
            if (this.checked) {
                map.addLayer(markersGroup.odcs);
            } else {
                map.removeLayer(markersGroup.odcs);
            }
        });
    }
    
    if (tampilOdp) {
        tampilOdp.addEventListener('change', function() {
            if (this.checked) {
                map.addLayer(markersGroup.odps);
            } else {
                map.removeLayer(markersGroup.odps);
            }
        });
    }
    
    if (tampilTiang) {
        tampilTiang.addEventListener('change', function() {
            if (this.checked) {
                map.addLayer(markersGroup.tiang);
            } else {
                map.removeLayer(markersGroup.tiang);
            }
        });
    }
    
    var tampilKabel = document.getElementById('show-kabel');
    if (tampilKabel) {
        tampilKabel.addEventListener('change', function() {
            // Gunakan fungsi aturVisibilityKabel untuk handle show/hide
            aturVisibilityKabel();
        });
    }
    
    var tampilHomepass = document.getElementById('show-homepass');
    if (tampilHomepass) {
        tampilHomepass.addEventListener('change', function() {
            if (this.checked) {
                map.addLayer(markersGroup.homepass);
            } else {
                map.removeLayer(markersGroup.homepass);
            }
        });
    }
    
    if (tampilPelanggan) {
        tampilPelanggan.addEventListener('change', function() {
            if (this.checked) {
                map.addLayer(markersGroup.customers);
            } else {
                map.removeLayer(markersGroup.customers);
            }
        });
    }
}

function tampilkanDetailRouter(id) {
    var router = null;
    if (networkData.routers) {
        router = networkData.routers.find(function(r) {
            return r.id == id;
        });
    }
    
    if (!router) {
        Swal.fire({ icon: 'error', title: LANG.alert_error, text: LANG.alert_router_not_found });
        return;
    }
    
    window.currentDetailRouterId = id;
    
    // Isi form
    document.getElementById('detailRouterId').value = router.id;
    document.getElementById('detailRouterCoordinatesHidden').value = router.coordinates;
    document.getElementById('detailRouterCoordinates').value = router.coordinates;
    document.getElementById('detailRouterName').value = router.name;
    document.getElementById('detailRouterIpAddress').value = router.ip_address || '-';
    document.getElementById('detailRouterDescription').value = router.description || '';
    document.getElementById('detailRouterCoverage').value = router.coverage || 1000;
    document.getElementById('detailRouterEnabled').value = router.enabled;
    
    document.getElementById('modalDetailRouterOverlay').classList.add('active');
}

function tutupModalDetailRouter() {
    document.getElementById('modalDetailRouterOverlay').classList.remove('active');
    window.currentDetailRouterId = null;
}

function aktifkanModeRemapRouter() {
    tutupModalDetailRouter();
    aktifkanModePick('remap-router');
}

function bukaKembaliModalDetailRouter(coordinates) {
    document.getElementById('detailRouterCoordinatesHidden').value = coordinates;
    document.getElementById('detailRouterCoordinates').value = coordinates;
    document.getElementById('modalDetailRouterOverlay').classList.add('active');
    
    Swal.fire({
        icon: 'success',
        title: LANG.alert_location_updated,
        text: LANG.alert_location_updated_text,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000
    });
}

function simpanEditRouter() {
    var routerId = document.getElementById('detailRouterId').value;
    var name = document.getElementById('detailRouterName').value;
    var coordinates = document.getElementById('detailRouterCoordinatesHidden').value;
    
    if (!name) {
        Swal.fire({ icon: 'error', title: LANG.alert_error, text: LANG.alert_router_name_required });
        return;
    }
    
    var formData = {
        router_id: routerId,
        name: name,
        coordinates: coordinates,
        description: document.getElementById('detailRouterDescription').value,
        coverage: document.getElementById('detailRouterCoverage').value || 1000,
        enabled: document.getElementById('detailRouterEnabled').value
    };
    
    Swal.fire({
        title: LANG.saving,
        text: LANG.please_wait,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: function() { Swal.showLoading(); }
    });
    
    fetch(baseUrl + 'plugin/network_mapping/update-router', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(formData)
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: LANG.alert_success,
                text: data.message,
                confirmButtonColor: '#6366f1'
            }).then(function() {
                location.reload();
            });
        } else {
            Swal.fire({ icon: 'error', title: LANG.alert_failed, text: data.message });
        }
    })
    .catch(function(error) {
        Swal.fire({ icon: 'error', title: LANG.alert_error, text: LANG.alert_save_error + ': ' + error.message });
    });
}

function hapusRouter() {
    if (!window.currentDetailRouterId) return;
    
    Swal.fire({
        title: LANG.confirm_delete_router,
        text: LANG.alert_router_delete_permanent,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: LANG.btn_yes_delete,
        cancelButtonText: LANG.btn_cancel,
        confirmButtonColor: '#ef4444',
        reverseButtons: true
    }).then(function(result) {
        if (result.isConfirmed) {
            fetch(baseUrl + 'plugin/network_mapping/delete-router', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ router_id: window.currentDetailRouterId })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: LANG.alert_success,
                        text: data.message
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: LANG.alert_failed, text: data.message });
                }
            });
        }
    });
}

function tampilkanDetailOdp(id) {
    // Cari data ODP dari networkData
    var odp = null;
    if (networkData.odps) {
        odp = networkData.odps.find(function(o) {
            return o.id == id;
        });
    }
    
    if (odp) {
        var detailHtml = '<div class="text-left">' +
            '<h4><i class="fa fa-circle-o text-warning"></i> ' + odp.name + '</h4>' +
            '<hr>' +
            '<p><strong>' + LANG.form_description + ':</strong> ' + (odp.description || LANG.no_description) + '</p>' +
            '<p><strong>' + LANG.form_coordinates + ':</strong> ' + odp.coordinates + '</p>' +
            '<p><strong>' + LANG.form_address + ':</strong> ' + (odp.address || LANG.js_no_address) + '</p>' +
            '<p><strong>' + LANG.text_port + ':</strong> ' + odp.used_ports + '/' + odp.total_ports + ' (' + LANG.form_available + ': ' + odp.available_ports + ')</p>' +
            '<p><strong>' + LANG.odc_connected + ':</strong> ' + (odp.odc_id ? 'ODC #' + odp.odc_id : LANG.no_odc_connected) + '</p>' +
            '<hr>' +
            '<div class="text-center">' +
                '<button type="button" class="btn btn-info center-map-btn">' + LANG.center_on_map + '</button>' +
            '</div>' +
        '</div>';
        
        Swal.fire({
            title: LANG.modal_detail_odp,
            html: detailHtml,
            width: 600,
            showConfirmButton: true,
            confirmButtonText: '<i class="fa fa-edit"></i> ' + LANG.modal_edit_odp,
            confirmButtonColor: '#f0ad4e',
            showCancelButton: true,
            cancelButtonText: LANG.btn_close,
            showCloseButton: true,
            didOpen: () => {
                var centerBtn = document.querySelector('.swal2-html-container .center-map-btn');
                if (centerBtn) {
                    centerBtn.addEventListener('click', function() {
                        pusatkanPetaKeOdp(odp.id);
                    });
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                var protocol = window.location.protocol;
                var host = window.location.host;
                var editUrl = protocol + '//' + host + '/?_route=plugin/network_mapping/edit-odp&id=' + odp.id;
                
                Swal.fire({
                    title: LANG.redirecting,
                    text: LANG.opening_edit_page,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                setTimeout(() => {
                    window.location.href = editUrl;
                }, 500);
            }
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_odp_not_found,
            text: LANG.alert_odp_not_found_text
        });
    }
}

// Fungsi untuk menampilkan modal detail OLT
function tampilkanDetailOlt(id) {
    // Cari data OLT dari networkData
    var olt = null;
    if (networkData.olts) {
        olt = networkData.olts.find(function(o) {
            return o.id == id;
        });
    }
    
    if (olt) {
        // Simpan ID OLT
        document.getElementById('detailOltId').value = id;
        window.currentDetailOltId = id;
        window.currentDetailOltData = olt;
        
        // Isi data ke modal - EDITABLE FIELDS
        document.getElementById('detailOltName').value = olt.name || '';
        document.getElementById('detailOltModel').value = olt.model || '';
        document.getElementById('detailOltSerialNumber').value = olt.serial_number || '';
        document.getElementById('detailOltIpAddress').value = olt.ip_address || '';
        document.getElementById('detailOltAddress').value = olt.address || '';
        document.getElementById('detailOltDescription').value = olt.description || '';
        
        // Brand - Select dropdown
        var brandSelect = document.getElementById('detailOltBrand');
        brandSelect.value = olt.brand || '';
        
        // Status - Select dropdown
        var statusSelect = document.getElementById('detailOltStatus');
        statusSelect.value = olt.status || 'Active';
        
        // Coordinates
        document.getElementById('detailOltCoordinates').value = olt.coordinates || '';
        document.getElementById('detailOltCoordinatesHidden').value = olt.coordinates || '';
        
        // ==================== PARENT CONNECTION (EDITABLE) ====================
        var parentTypeSelect = document.getElementById('detailOltParentType');
        var detailGroupParentRouter = document.getElementById('detailGroupParentRouter');
        var detailGroupParentOlt = document.getElementById('detailGroupParentOlt');
        
        // Set parent type dropdown
        parentTypeSelect.value = olt.parent_type || '';
        
        // Simpan current parent untuk disable option
        window.currentOltParentType = olt.parent_type || '';
        window.currentOltParentRouterId = olt.parent_router_id || '';
        window.currentOltParentOltId = olt.parent_olt_id || '';
        
        // Reset semua parent dropdown visibility
        detailGroupParentRouter.style.display = 'none';
        detailGroupParentOlt.style.display = 'none';
        
        // Tampilkan dropdown yang sesuai dan set value
        if (olt.parent_type === 'router') {
            detailGroupParentRouter.style.display = 'block';
            document.getElementById('detailOltParentRouter').value = olt.parent_router_id || '';
            // Disable current option
            disableCurrentParentOption('detailOltParentRouter', olt.parent_router_id);
        } else if (olt.parent_type === 'olt') {
            detailGroupParentOlt.style.display = 'block';
            document.getElementById('detailOltParentOltSelect').value = olt.parent_olt_id || '';
            // Disable current option
            disableCurrentParentOption('detailOltParentOltSelect', olt.parent_olt_id);
        }
        
        // Disable current parent type option
        disableCurrentParentTypeOption(olt.parent_type);
        
        // ==================== PORT CONFIGURATION (EDITABLE) ====================
        document.getElementById('detailOltTotalPorts').value = olt.total_ports || 8;
        document.getElementById('detailOltUsedPorts').value = olt.used_ports || 0;
        
        // Update port tersedia display
        updateDetailPortTersedia();
        
        // Simpan ports_data untuk di-load ke form
        window.currentOltPortsData = olt.ports_data || [];
        
        // Generate multi-port fields berdasarkan used_ports
        generateDetailPortFields(parseInt(olt.used_ports) || 0, parseInt(olt.total_ports) || 8);
        
        // Tampilkan modal
        document.getElementById('modalDetailOltOverlay').classList.add('active');
    } else {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_olt_not_found,
            text: LANG.alert_olt_not_found_text
        });
    }
}

// ==================== FUNGSI BARU UNTUK MULTI-PORT DETAIL OLT ====================

// Fungsi untuk disable option parent type yang sedang aktif
function disableCurrentParentTypeOption(currentType) {
    var parentTypeSelect = document.getElementById('detailOltParentType');
    var options = parentTypeSelect.querySelectorAll('option');
    
    options.forEach(function(option) {
        // Reset semua dulu
        option.disabled = false;
        option.style.color = '';
        
        // Disable yang sedang aktif (kecuali "Tidak Ada Koneksi")
        if (option.value === currentType && currentType !== '') {
            option.disabled = true;
            option.style.color = '#999';
        }
    });
}

// Fungsi untuk disable option parent yang sedang dipilih
function disableCurrentParentOption(selectId, currentValue) {
    var selectElement = document.getElementById(selectId);
    if (!selectElement) return;
    
    var options = selectElement.querySelectorAll('option');
    
    options.forEach(function(option) {
        // Reset semua dulu
        option.disabled = false;
        option.style.color = '';
        
        // Disable yang sedang aktif (kecuali option default)
        if (option.value === String(currentValue) && currentValue !== '' && currentValue !== null) {
            option.disabled = true;
            option.style.color = '#999';
        }
    });
}

// Fungsi toggle parent select di modal detail
function toggleDetailParentSelect() {
    var parentType = document.getElementById('detailOltParentType').value;
    var groupRouter = document.getElementById('detailGroupParentRouter');
    var groupOlt = document.getElementById('detailGroupParentOlt');
    
    // Reset visibility
    groupRouter.style.display = 'none';
    groupOlt.style.display = 'none';
    
    if (parentType === 'router') {
        groupRouter.style.display = 'block';
        // Reset dropdown value jika beda dari current
        if (window.currentOltParentType !== 'router') {
            document.getElementById('detailOltParentRouter').value = '';
        }
    } else if (parentType === 'olt') {
        groupOlt.style.display = 'block';
        // Reset dropdown value jika beda dari current
        if (window.currentOltParentType !== 'olt') {
            document.getElementById('detailOltParentOltSelect').value = '';
        }
    }
}

// Fungsi untuk ubah port di modal detail dengan tombol +/-
function ubahDetailPort(tipe, delta) {
    var inputId = tipe === 'total' ? 'detailOltTotalPorts' : 'detailOltUsedPorts';
    var input = document.getElementById(inputId);
    var nilai = parseInt(input.value) || 0;
    
    var totalPorts = parseInt(document.getElementById('detailOltTotalPorts').value) || 8;
    var usedPorts = parseInt(document.getElementById('detailOltUsedPorts').value) || 0;
    
    if (tipe === 'total') {
        nilai += delta;
        if (nilai < 1) nilai = 1;
        if (nilai > 128) nilai = 128;
        // Total port tidak boleh kurang dari used ports
        if (nilai < usedPorts) nilai = usedPorts;
        input.value = nilai;
        
        // Jika total port berubah, regenerate semua dropdown PON dengan opsi baru
        // Simpan data yang sudah ada dulu
        updatePortsDataFromFields();
        // Regenerate dengan total port baru
        generateDetailPortFields(usedPorts, nilai);
    } else {
        // Used ports
        nilai += delta;
        if (nilai < 0) nilai = 0;
        if (nilai > totalPorts) nilai = totalPorts;
        input.value = nilai;
        
        // Regenerate port fields
        generateDetailPortFields(nilai, totalPorts);
    }
    
    // Update tampilan port tersedia
    updateDetailPortTersedia();
}

// Fungsi untuk update tampilan port tersedia di modal detail
function updateDetailPortTersedia() {
    var totalPorts = parseInt(document.getElementById('detailOltTotalPorts').value) || 0;
    var usedPorts = parseInt(document.getElementById('detailOltUsedPorts').value) || 0;
    var tersedia = totalPorts - usedPorts;
    
    var portTersediaElement = document.getElementById('detailPortTersedia');
    if (portTersediaElement) {
        portTersediaElement.textContent = tersedia;
        
        // Ubah warna background berdasarkan availability
        var portInfoSimple = portTersediaElement.closest('.port-info-simple');
        if (portInfoSimple) {
            if (tersedia === 0) {
                portInfoSimple.style.background = '#fee2e2';
                portInfoSimple.style.borderColor = '#fca5a5';
                portInfoSimple.style.color = '#7f1d1d';
                portInfoSimple.querySelector('strong').style.color = '#991b1b';
            } else if (tersedia <= totalPorts * 0.2) {
                portInfoSimple.style.background = '#fef3c7';
                portInfoSimple.style.borderColor = '#fde047';
                portInfoSimple.style.color = '#713f12';
                portInfoSimple.querySelector('strong').style.color = '#854d0e';
            } else {
                portInfoSimple.style.background = '#ecfdf5';
                portInfoSimple.style.borderColor = '#a7f3d0';
                portInfoSimple.style.color = '#065f46';
                portInfoSimple.querySelector('strong').style.color = '#047857';
            }
        }
    }
}

// Fungsi untuk generate multi-port fields di modal detail
function generateDetailPortFields(usedPorts, totalPorts) {
    var container = document.getElementById('detailPortsContainer');
    if (!container) return;
    
    // Kosongkan container
    container.innerHTML = '';
    
    if (usedPorts <= 0) {
        container.innerHTML = '<p class="text-muted" style="text-align: center; padding: 10px;"><i class="fa fa-info-circle"></i> Tidak ada port yang terpakai</p>';
        return;
    }
    
    // Header
    var headerHtml = '<div class="ports-fields-header" style="background: #f0f9ff; padding: 10px; border-radius: 6px; margin-bottom: 10px;">' +
        '<strong><i class="fa fa-plug"></i> ' + LANG.js_detail + ' ' + usedPorts + ' ' + LANG.js_ports_used + '</strong>' +
        '</div>';
    container.innerHTML = headerHtml;
    
    // Generate fields untuk setiap port terpakai
    for (var i = 0; i < usedPorts; i++) {
        var portIndex = i + 1;
        
        // Cek apakah ada data existing
        var existingPon = '';
        var existingLabel = '';
        if (window.currentOltPortsData && window.currentOltPortsData[i]) {
            existingPon = window.currentOltPortsData[i].pon || '';
            existingLabel = window.currentOltPortsData[i].label || '';
        }
        
        var fieldHtml = '<div class="port-field-item" style="background: #fafafa; padding: 12px; border-radius: 6px; margin-bottom: 10px; border: 1px solid #e5e7eb;">' +
            '<div style="font-weight: 600; margin-bottom: 8px; color: #4b5563;"><i class="fa fa-circle" style="font-size: 8px; color: #8b5cf6;"></i> ' + LANG.js_port_number + portIndex + '</div>' +
            '<div class="row">' +
                '<div class="col-md-6">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.js_pon_number_label + '</label>' +
                        '<select class="form-control input-sm" id="detailPonSelect_' + i + '" onchange="onPonSelectChange(' + i + ')">' +
                            generatePonOptionsHtml(totalPorts, existingPon) +
                        '</select>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.js_port_label_label + '</label>' +
                        '<input type="text" class="form-control input-sm" id="detailPortLabel_' + i + '" value="' + escapeHtml(existingLabel) + '" placeholder="' + LANG.js_port_label_placeholder + '" onchange="updatePortsDataFromFields()">' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        container.innerHTML += fieldHtml;
    }
    
    // Setelah semua field di-generate, update disabled state untuk semua dropdown
    updateAllPonDropdownsDisabledState();
}

// Fungsi yang dipanggil ketika PON dropdown berubah
function onPonSelectChange(changedIndex) {
    // Update data dulu
    updatePortsDataFromFields();
    
    // Update disabled state untuk semua dropdown PON
    updateAllPonDropdownsDisabledState();
}

// Fungsi untuk update disabled state semua dropdown PON
function updateAllPonDropdownsDisabledState() {
    var usedPorts = parseInt(document.getElementById('detailOltUsedPorts').value) || 0;
    
    // Kumpulkan semua PON yang sudah dipilih
    var selectedPons = [];
    for (var i = 0; i < usedPorts; i++) {
        var select = document.getElementById('detailPonSelect_' + i);
        if (select && select.value) {
            selectedPons.push({
                index: i,
                value: select.value
            });
        }
    }
    
    // Update setiap dropdown
    for (var i = 0; i < usedPorts; i++) {
        var select = document.getElementById('detailPonSelect_' + i);
        if (!select) continue;
        
        var currentValue = select.value;
        var options = select.querySelectorAll('option');
        
        options.forEach(function(option) {
            // Skip option default (value kosong)
            if (!option.value) {
                option.disabled = false;
                option.style.color = '';
                return;
            }
            
            // Cek apakah PON ini sudah dipilih di dropdown lain
            var isUsedByOther = selectedPons.some(function(item) {
                return item.value === option.value && item.index !== i;
            });
            
            if (isUsedByOther) {
                // Disable karena sudah dipakai dropdown lain
                option.disabled = true;
                option.style.color = '#999';
            } else {
                // Enable
                option.disabled = false;
                option.style.color = '';
            }
        });
    }
}

// Fungsi untuk generate options PON
function generatePonOptionsHtml(totalPorts, selectedValue) {
    var html = '<option value="">' + LANG.js_select_pon + '</option>';
    for (var i = 1; i <= totalPorts; i++) {
        var ponValue = 'PON ' + i;
        var selected = (ponValue === selectedValue) ? 'selected' : '';
        html += '<option value="' + ponValue + '" ' + selected + '>' + ponValue + '</option>';
    }
    return html;
}

// Fungsi untuk escape HTML
function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Fungsi untuk update ports data dari fields ke window variable
function updatePortsDataFromFields() {
    var usedPorts = parseInt(document.getElementById('detailOltUsedPorts').value) || 0;
    var portsData = [];
    
    for (var i = 0; i < usedPorts; i++) {
        var ponSelect = document.getElementById('detailPonSelect_' + i);
        var labelInput = document.getElementById('detailPortLabel_' + i);
        
        if (ponSelect && labelInput) {
            portsData.push({
                pon: ponSelect.value,
                label: labelInput.value
            });
        }
    }
    
    window.currentOltPortsData = portsData;
}

// Fungsi untuk mendapatkan ports data sebagai JSON string
function getPortsDataJson() {
    updatePortsDataFromFields();
    return JSON.stringify(window.currentOltPortsData || []);
}

// ==================== END FUNGSI MULTI-PORT ====================

// Fungsi untuk menutup modal detail OLT
function tutupModalDetailOlt() {
    document.getElementById('modalDetailOltOverlay').classList.remove('active');
    window.currentDetailOltId = null;
    window.currentDetailOltData = null;
    
    // Clear remap data
    window.remapOltId = null;
    window.remapOltData = null;
    
    // Batalkan mode pick jika masih aktif
    if (isPickMode && pickModeType === 'remap-olt') {
        batalkanModePick();
    }
    
    // Hapus temp marker jika ada
    if (tempMarker) {
        map.removeLayer(tempMarker);
        tempMarker = null;
    }
}

// Fungsi untuk mengaktifkan mode remap koordinat OLT
function aktifkanModeRemapOlt() {
    // Simpan data OLT yang sedang di-edit untuk dibuka kembali nanti
    window.remapOltId = document.getElementById('detailOltId').value;
    
    // Update ports data dari fields sebelum menyimpan
    updatePortsDataFromFields();
    
    window.remapOltData = {
        name: document.getElementById('detailOltName').value,
        brand: document.getElementById('detailOltBrand').value,
        model: document.getElementById('detailOltModel').value,
        serial_number: document.getElementById('detailOltSerialNumber').value,
        ip_address: document.getElementById('detailOltIpAddress').value,
        status: document.getElementById('detailOltStatus').value,
        address: document.getElementById('detailOltAddress').value,
        description: document.getElementById('detailOltDescription').value,
        // Tambahan: Parent connection
        parent_type: document.getElementById('detailOltParentType').value,
        parent_router_id: document.getElementById('detailOltParentRouter').value,
        parent_olt_id: document.getElementById('detailOltParentOltSelect').value,
        // Tambahan: Port configuration
        total_ports: document.getElementById('detailOltTotalPorts').value,
        used_ports: document.getElementById('detailOltUsedPorts').value,
        ports_data: window.currentOltPortsData || []
    };
    
    // Tutup modal detail dulu
    document.getElementById('modalDetailOltOverlay').classList.remove('active');
    
    // Gunakan sistem pick mode yang sudah ada
    isPickMode = true;
    pickModeType = 'remap-olt';
    
    // Tampilkan indikator
    var pickIndicator = document.getElementById('pickModeIndicator');
    var pickText = pickIndicator.querySelector('span');
    pickText.textContent = 'Klik pada peta untuk mengubah lokasi OLT';
    pickIndicator.classList.add('active');
    
    // Ubah cursor map
    document.getElementById('map').style.cursor = 'crosshair';
    
    // Tambahkan event listener untuk klik map dengan delay
    setTimeout(function() {
        map.on('click', onMapClickPick);
    }, 300);
}

// Fungsi untuk buka kembali modal detail OLT setelah pilih lokasi baru
function bukaKembaliModalDetailOlt(newCoordinates) {
    // Cari data OLT original dari networkData
    var olt = null;
    if (networkData.olts && window.remapOltId) {
        olt = networkData.olts.find(function(o) {
            return o.id == window.remapOltId;
        });
    }
    
    if (olt && window.remapOltData) {
        // Isi kembali semua data (gunakan data yang sudah diedit user)
        document.getElementById('detailOltId').value = window.remapOltId;
        document.getElementById('detailOltName').value = window.remapOltData.name;
        document.getElementById('detailOltBrand').value = window.remapOltData.brand;
        document.getElementById('detailOltModel').value = window.remapOltData.model;
        document.getElementById('detailOltSerialNumber').value = window.remapOltData.serial_number;
        document.getElementById('detailOltIpAddress').value = window.remapOltData.ip_address;
        document.getElementById('detailOltStatus').value = window.remapOltData.status;
        document.getElementById('detailOltAddress').value = window.remapOltData.address;
        document.getElementById('detailOltDescription').value = window.remapOltData.description;
        
        // Update HANYA koordinat dengan yang baru
        document.getElementById('detailOltCoordinates').value = newCoordinates;
        document.getElementById('detailOltCoordinatesHidden').value = newCoordinates;
        
        // ==================== RESTORE PARENT CONNECTION ====================
        var parentTypeSelect = document.getElementById('detailOltParentType');
        var detailGroupParentRouter = document.getElementById('detailGroupParentRouter');
        var detailGroupParentOlt = document.getElementById('detailGroupParentOlt');
        
        // Restore parent type dari remapOltData (data yang sudah diedit user)
        var savedParentType = window.remapOltData.parent_type || olt.parent_type || '';
        parentTypeSelect.value = savedParentType;
        
        // Reset visibility
        detailGroupParentRouter.style.display = 'none';
        detailGroupParentOlt.style.display = 'none';
        
        if (savedParentType === 'router') {
            detailGroupParentRouter.style.display = 'block';
            document.getElementById('detailOltParentRouter').value = window.remapOltData.parent_router_id || olt.parent_router_id || '';
        } else if (savedParentType === 'olt') {
            detailGroupParentOlt.style.display = 'block';
            document.getElementById('detailOltParentOltSelect').value = window.remapOltData.parent_olt_id || olt.parent_olt_id || '';
        }
        
        // ==================== RESTORE PORT CONFIGURATION ====================
        var savedTotalPorts = window.remapOltData.total_ports || olt.total_ports || 8;
        var savedUsedPorts = window.remapOltData.used_ports || olt.used_ports || 0;
        
        document.getElementById('detailOltTotalPorts').value = savedTotalPorts;
        document.getElementById('detailOltUsedPorts').value = savedUsedPorts;
        
        // Update port tersedia display
        updateDetailPortTersedia();
        
        // Restore ports data
        window.currentOltPortsData = window.remapOltData.ports_data || olt.ports_data || [];
        
        // Regenerate port fields
        generateDetailPortFields(parseInt(savedUsedPorts), parseInt(savedTotalPorts));
        
        // Simpan ID untuk fungsi lain
        window.currentDetailOltId = window.remapOltId;
        window.currentDetailOltData = olt;
        
        // Buka modal
        document.getElementById('modalDetailOltOverlay').classList.add('active');
        
        // Tampilkan notifikasi sukses
        Swal.fire({
            icon: 'success',
            title: LANG.alert_location_changed,
            text: LANG.alert_location_changed_text,
            timer: 2000,
            showConfirmButton: false,
            customClass: {
                container: 'swal-high-zindex'
            }
        });
        
        // Clear temporary data
        window.remapOltId = null;
        window.remapOltData = null;
    }
}

// Fungsi untuk simpan edit OLT
function simpanEditOlt() {
    var olt_id = document.getElementById('detailOltId').value;
    var name = document.getElementById('detailOltName').value.trim();
    var brand = document.getElementById('detailOltBrand').value;
    var model = document.getElementById('detailOltModel').value.trim();
    var serial_number = document.getElementById('detailOltSerialNumber').value.trim();
    var ip_address = document.getElementById('detailOltIpAddress').value.trim();
    var status = document.getElementById('detailOltStatus').value;
    var coordinates = document.getElementById('detailOltCoordinatesHidden').value;
    var address = document.getElementById('detailOltAddress').value.trim();
    var description = document.getElementById('detailOltDescription').value.trim();
    
    // Tambahan: Parent connection
    var parent_type = document.getElementById('detailOltParentType').value;
    var parent_router_id = '';
    var parent_olt_id = '';
    
    if (parent_type === 'router') {
        parent_router_id = document.getElementById('detailOltParentRouter').value;
    } else if (parent_type === 'olt') {
        parent_olt_id = document.getElementById('detailOltParentOltSelect').value;
    }
    
    // Tambahan: Port configuration
    var total_ports = document.getElementById('detailOltTotalPorts').value;
    var used_ports = document.getElementById('detailOltUsedPorts').value;
    var ports_data = getPortsDataJson();
    
    // Validasi
    if (!name) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_validation_failed,
            text: LANG.alert_olt_name_required
        });
        return;
    }
    
    if (!coordinates) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_validation_failed,
            text: LANG.alert_coordinates_required
        });
        return;
    }
    
    // Validasi parent jika dipilih
    if (parent_type === 'router' && !parent_router_id) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_validation_failed,
            text: LANG.alert_select_router_parent
        });
        return;
    }
    
    if (parent_type === 'olt' && !parent_olt_id) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_validation_failed,
            text: LANG.alert_select_olt_parent
        });
        return;
    }
    
    // Konfirmasi
    Swal.fire({
        title: LANG.alert_save_changes_olt_title,
        text: LANG.alert_save_changes_olt_text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-save"></i> ' + LANG.alert_yes_save,
        cancelButtonText: LANG.alert_cancel,
        confirmButtonColor: '#8b5cf6',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            // Loading
            Swal.fire({
                title: LANG.alert_saving,
                text: LANG.alert_please_wait,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Kirim data ke server
            fetch(baseUrl + 'plugin/network_mapping/update-olt', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    olt_id: olt_id,
                    name: name,
                    brand: brand,
                    model: model,
                    serial_number: serial_number,
                    ip_address: ip_address,
                    status: status,
                    coordinates: coordinates,
                    address: address,
                    description: description,
                    parent_type: parent_type,
                    parent_router_id: parent_router_id,
                    parent_olt_id: parent_olt_id,
                    total_ports: total_ports,
                    used_ports: used_ports,
                    ports_data: ports_data
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: LANG.alert_success,
                        text: LANG.alert_olt_changes_saved,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Reload halaman untuk update data
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: LANG.alert_failed,
                        text: data.message || LANG.alert_save_error
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: LANG.alert_error,
                    text: LANG.alert_server_connection_failed
                });
            });
        }
    });
}

// Fungsi untuk hapus OLT
function hapusOlt() {
    var olt_id = document.getElementById('detailOltId').value;
    var olt_name = document.getElementById('detailOltName').value;
    
    if (!olt_id) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_error,
            text: LANG.alert_olt_id_not_found
        });
        return;
    }
    
    // Konfirmasi hapus
    Swal.fire({
        title: LANG.alert_delete_olt_title,
        text: LANG.alert_delete_olt_prefix + ' "' + olt_name + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-trash"></i> ' + LANG.alert_yes_delete,
        cancelButtonText: LANG.alert_cancel,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Loading
            Swal.fire({
                title: LANG.alert_deleting,
                text: LANG.alert_please_wait,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Kirim request delete ke server
            fetch(baseUrl + 'plugin/network_mapping/delete-olt', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    olt_id: olt_id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: LANG.alert_success,
                        text: LANG.alert_olt_deleted_success,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Reload halaman untuk update data
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: LANG.alert_failed,
                        text: data.message || LANG.alert_olt_delete_error
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: LANG.alert_error,
                    text: LANG.alert_server_connection_failed
                });
            });
        }
    });
}

// Fungsi untuk pusatkan peta ke OLT dari modal detail
function pusatkanPetaKeOltDetail() {
    if (window.currentDetailOltId) {
        var olt = networkData.olts.find(function(o) { return o.id == window.currentDetailOltId; });
        if (olt) {
            var coords = olt.coordinates.split(',').map(parseFloat);
            map.setView(coords, 18);
            tutupModalDetailOlt();
        }
    }
}

// Fungsi bantuan untuk memusatkan peta pada elemen tertentu
function pusatkanPetaKeRouter(id) {
    var router = networkData.routers.find(function(r) { return r.id == id; });
    if (router) {
        var coords = router.coordinates.split(',').map(parseFloat);
        map.setView(coords, 16);
        Swal.close();
    }
}

function pusatkanPetaKeOdc(id) {
    var odc = networkData.odcs.find(function(o) { return o.id == id; });
    if (odc) {
        var coords = odc.coordinates.split(',').map(parseFloat);
        map.setView(coords, 18);
        Swal.close();
    }
}

function pusatkanPetaKeOdp(id) {
    var odp = networkData.odps.find(function(o) { return o.id == id; });
    if (odp) {
        var coords = odp.coordinates.split(',').map(parseFloat);
        map.setView(coords, 18);
        Swal.close();
    }
}

// ==================== FUNGSI ODC ====================

// Fungsi untuk tutup modal ODC
function tutupModalOdc() {
    document.getElementById('modalOdcOverlay').classList.remove('active');
    document.getElementById('formOdc').reset();
    document.getElementById('odcTotalPorts').value = 8;
    document.getElementById('odcUsedPorts').value = 0;
    
    // Reset parent selection dan port detail
    document.getElementById('odcParentType').value = '';
    toggleOdcParentSelect();
    
    // Reset multi-port fields
    resetAddOdcPortFields();
    updateOdcPortTersediaAdd();
    
    // Reset foto preview
    hapusFotoOdcAddPreview();
    
    // Reset mode ke Box Baru (default)
    setOdcBoxMode('new');
    
    // Hapus temp marker
    if (tempMarker) {
        map.removeLayer(tempMarker);
        tempMarker = null;
    }
}

// Toggle tampilan dropdown parent ODC
function toggleOdcParentSelect() {
    var parentType = document.getElementById('odcParentType').value;
    var groupOlt = document.getElementById('odcGroupParentOlt');
    var groupOdc = document.getElementById('odcGroupParentOdc');
    var selectOdc = document.getElementById('odcParentOdc');
    var helpNoOdc = document.getElementById('odcHelpNoOdc');
    
    // Reset visibility
    groupOlt.style.display = 'none';
    groupOdc.style.display = 'none';
    document.getElementById('odcParentOlt').value = '';
    document.getElementById('odcParentOdc').value = '';
    
    if (parentType === 'olt') {
        groupOlt.style.display = 'block';
    } else if (parentType === 'odc') {
        groupOdc.style.display = 'block';
        
        // Cek apakah ada ODC lain
        var odcOptions = selectOdc.querySelectorAll('option');
        if (odcOptions.length <= 1) {
            selectOdc.disabled = true;
            helpNoOdc.style.display = 'block';
        } else {
            selectOdc.disabled = false;
            helpNoOdc.style.display = 'none';
        }
    }
}

// ==================== FUNGSI MULTI-PORT ADD ODC ====================

// Fungsi untuk ubah port di modal Add ODC dengan tombol +/-
function ubahOdcPortAdd(tipe, delta) {
    var inputId = tipe === 'total' ? 'odcTotalPorts' : 'odcUsedPorts';
    var input = document.getElementById(inputId);
    var nilai = parseInt(input.value) || 0;
    
    var totalPorts = parseInt(document.getElementById('odcTotalPorts').value) || 8;
    var usedPorts = parseInt(document.getElementById('odcUsedPorts').value) || 0;
    
    if (tipe === 'total') {
        nilai += delta;
        if (nilai < 1) nilai = 1;
        if (nilai > 128) nilai = 128;
        if (nilai < usedPorts) nilai = usedPorts;
        input.value = nilai;
        
        updateAddOdcPortsDataFromFields();
        generateAddOdcPortFields(usedPorts, nilai);
    } else {
        nilai += delta;
        if (nilai < 0) nilai = 0;
        if (nilai > totalPorts) nilai = totalPorts;
        input.value = nilai;
        
        generateAddOdcPortFields(nilai, totalPorts);
    }
    
    updateOdcPortTersediaAdd();
}

// Fungsi untuk update tampilan port tersedia di modal Add ODC
function updateOdcPortTersediaAdd() {
    var totalPorts = parseInt(document.getElementById('odcTotalPorts').value) || 0;
    var usedPorts = parseInt(document.getElementById('odcUsedPorts').value) || 0;
    var tersedia = totalPorts - usedPorts;
    
    var portTersediaElement = document.getElementById('odcPortTersedia');
    if (portTersediaElement) {
        portTersediaElement.textContent = tersedia;
        
        var portInfoSimple = portTersediaElement.closest('.port-info-simple');
        if (portInfoSimple) {
            if (tersedia === 0) {
                portInfoSimple.style.background = '#fee2e2';
                portInfoSimple.style.borderColor = '#fca5a5';
                portInfoSimple.style.color = '#7f1d1d';
                portInfoSimple.querySelector('strong').style.color = '#991b1b';
            } else if (tersedia <= totalPorts * 0.2) {
                portInfoSimple.style.background = '#fef3c7';
                portInfoSimple.style.borderColor = '#fde047';
                portInfoSimple.style.color = '#713f12';
                portInfoSimple.querySelector('strong').style.color = '#854d0e';
            } else {
                portInfoSimple.style.background = '#ecfdf5';
                portInfoSimple.style.borderColor = '#a7f3d0';
                portInfoSimple.style.color = '#065f46';
                portInfoSimple.querySelector('strong').style.color = '#047857';
            }
        }
    }
}

// Fungsi untuk generate multi-port fields di modal Add ODC
function generateAddOdcPortFields(usedPorts, totalPorts) {
    var container = document.getElementById('addOdcPortsContainer');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (usedPorts <= 0) {
        container.innerHTML = '<p class="text-muted" style="text-align: center; padding: 10px;"><i class="fa fa-info-circle"></i> ' + LANG.js_no_ports_used + '</p>';
        return;
    }
    
    var headerHtml = '<div class="ports-fields-header" style="background: #ecfdf5; padding: 10px; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid #10b981;">' +
        '<strong style="color: #065f46;"><i class="fa fa-plug"></i> ' + LANG.js_detail + ' ' + usedPorts + ' ' + LANG.js_ports_used + '</strong>' +
        '</div>';
    container.innerHTML = headerHtml;
    
    for (var i = 0; i < usedPorts; i++) {
        var portIndex = i + 1;
        
        var existingPort = '';
        var existingLabel = '';
        if (window.addOdcPortsData && window.addOdcPortsData[i]) {
            existingPort = window.addOdcPortsData[i].port || '';
            existingLabel = window.addOdcPortsData[i].label || '';
        }
        
        var fieldHtml = '<div class="port-field-item" style="background: #fafafa; padding: 12px; border-radius: 6px; margin-bottom: 10px; border: 1px solid #e5e7eb;">' +
            '<div style="font-weight: 600; margin-bottom: 8px; color: #4b5563;"><i class="fa fa-circle" style="font-size: 8px; color: #10b981;"></i> ' + LANG.js_port_number + portIndex + '</div>' +
            '<div class="row">' +
                '<div class="col-md-6">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.js_port_number_label + '</label>' +
                        '<select class="form-control input-sm" id="addOdcPortSelect_' + i + '" onchange="onAddOdcPortSelectChange(' + i + ')">' +
                            generateAddOdcPortOptionsHtml(totalPorts, existingPort) +
                        '</select>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.js_port_label_label + '</label>' +
                        '<input type="text" class="form-control input-sm" id="addOdcPortLabel_' + i + '" value="' + escapeHtml(existingLabel) + '" placeholder="' + LANG.js_port_label_placeholder_odc + '" onchange="updateAddOdcPortsDataFromFields()">' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        container.innerHTML += fieldHtml;
    }
    
    updateAllAddOdcPortDropdownsDisabledState();
}

// Fungsi untuk generate options Port di modal Add ODC
function generateAddOdcPortOptionsHtml(totalPorts, selectedValue) {
    var html = '<option value="">' + LANG.js_select_port + '</option>';
    for (var i = 1; i <= totalPorts; i++) {
        var portValue = 'Port ' + i;
        var selected = (portValue === selectedValue) ? 'selected' : '';
        html += '<option value="' + portValue + '" ' + selected + '>' + portValue + '</option>';
    }
    return html;
}

// Fungsi yang dipanggil ketika Port dropdown berubah di modal Add ODC
function onAddOdcPortSelectChange(changedIndex) {
    updateAddOdcPortsDataFromFields();
    updateAllAddOdcPortDropdownsDisabledState();
}

// Fungsi untuk update disabled state semua dropdown Port di modal Add ODC
function updateAllAddOdcPortDropdownsDisabledState() {
    var usedPorts = parseInt(document.getElementById('odcUsedPorts').value) || 0;
    
    var selectedPorts = [];
    for (var i = 0; i < usedPorts; i++) {
        var select = document.getElementById('addOdcPortSelect_' + i);
        if (select && select.value) {
            selectedPorts.push({
                index: i,
                value: select.value
            });
        }
    }
    
    for (var i = 0; i < usedPorts; i++) {
        var select = document.getElementById('addOdcPortSelect_' + i);
        if (!select) continue;
        
        var currentValue = select.value;
        var options = select.querySelectorAll('option');
        
        options.forEach(function(option) {
            if (!option.value) {
                option.disabled = false;
                option.style.color = '';
                return;
            }
            
            var isUsedByOther = selectedPorts.some(function(item) {
                return item.value === option.value && item.index !== i;
            });
            
            if (isUsedByOther) {
                option.disabled = true;
                option.style.color = '#999';
            } else {
                option.disabled = false;
                option.style.color = '';
            }
        });
    }
}

// Fungsi untuk update ports data dari fields ke window variable di modal Add ODC
function updateAddOdcPortsDataFromFields() {
    var usedPorts = parseInt(document.getElementById('odcUsedPorts').value) || 0;
    var portsData = [];
    
    for (var i = 0; i < usedPorts; i++) {
        var portSelect = document.getElementById('addOdcPortSelect_' + i);
        var labelInput = document.getElementById('addOdcPortLabel_' + i);
        
        if (portSelect && labelInput) {
            portsData.push({
                port: portSelect.value,
                label: labelInput.value
            });
        }
    }
    
    window.addOdcPortsData = portsData;
}

// Fungsi untuk mendapatkan ports data sebagai JSON string di modal Add ODC
function getAddOdcPortsDataJson() {
    updateAddOdcPortsDataFromFields();
    return JSON.stringify(window.addOdcPortsData || []);
}

// Fungsi untuk reset multi-port di modal Add ODC
function resetAddOdcPortFields() {
    window.addOdcPortsData = [];
    var container = document.getElementById('addOdcPortsContainer');
    if (container) {
        container.innerHTML = '<p class="text-muted" style="text-align: center; padding: 10px;"><i class="fa fa-info-circle"></i> ' + LANG.js_no_ports_used + '</p>';
    }
}

// ==================== END FUNGSI MULTI-PORT ADD ODC ====================

// Fungsi untuk simpan ODC
function simpanOdc() {
    var parentType = document.getElementById('odcParentType').value;
    var isNewBox = document.getElementById('odcIsNewBox').value;
    
    // Validasi parent connection
    if (parentType === 'olt' && !document.getElementById('odcParentOlt').value) {
        Swal.fire({
            icon: 'warning',
            title: LANG.alert_data_incomplete,
            text: LANG.alert_select_olt_parent,
            confirmButtonColor: '#10b981'
        });
        return;
    }
    
    if (parentType === 'odc' && !document.getElementById('odcParentOdc').value) {
        Swal.fire({
            icon: 'warning',
            title: LANG.alert_data_incomplete,
            text: LANG.alert_select_odc_parent,
            confirmButtonColor: '#10b981'
        });
        return;
    }
    
    // Validasi box existing
    if (isNewBox === 'no' && !document.getElementById('odcSelectBox').value) {
        Swal.fire({
            icon: 'warning',
            title: LANG.alert_data_incomplete,
            text: LANG.alert_select_box,
            confirmButtonColor: '#10b981'
        });
        return;
    }
    
    var name = document.getElementById('odcName').value;
    
    if (!name) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_error,
            text: LANG.alert_odc_name_required,
            confirmButtonColor: '#10b981'
        });
        return;
    }
    
    var portsDataJson = getAddOdcPortsDataJson();
    var fotoInput = document.getElementById('addOdcFoto');
    
    // Validasi ukuran foto (max 5MB)
    if (fotoInput && fotoInput.files.length > 0) {
        var fileSize = fotoInput.files[0].size;
        var maxSize = 5 * 1024 * 1024; // 5MB
        if (fileSize > maxSize) {
            Swal.fire({
                icon: 'error',
                title: LANG.alert_validation_failed,
                text: LANG.alert_photo_max_5mb,
                confirmButtonColor: '#ef4444'
            });
            return;
        }
    }
    
    Swal.fire({
        title: LANG.alert_saving,
        text: LANG.alert_please_wait,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: function() {
            Swal.showLoading();
        }
    });
    
    // Gunakan FormData untuk upload file
    var formData = new FormData();
    formData.append('name', name);
    formData.append('coordinates', document.getElementById('odcCoordinates').value);
    formData.append('address', document.getElementById('odcAddress').value);
    formData.append('total_ports', document.getElementById('odcTotalPorts').value);
    formData.append('used_ports', document.getElementById('odcUsedPorts').value);
    formData.append('status', document.getElementById('odcStatus').value);
    formData.append('description', document.getElementById('odcDescription').value);
    formData.append('parent_type', parentType);
    formData.append('parent_olt_id', document.getElementById('odcParentOlt').value);
    formData.append('parent_odc_id', document.getElementById('odcParentOdc').value);
    formData.append('ports_data', portsDataJson);
    formData.append('box_id', document.getElementById('odcBoxId').value);
    formData.append('is_new_box', isNewBox);
    
    // Tambahkan foto jika ada (hanya untuk Box Baru)
    if (isNewBox === 'yes' && fotoInput && fotoInput.files.length > 0) {
        formData.append('foto_odc', fotoInput.files[0]);
    }
    
    fetch(baseUrl + 'plugin/network_mapping/save-odc', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.status === 'success') {
            tutupModalOdc();
            
            Swal.fire({
                icon: 'success',
                title: LANG.alert_success,
                text: data.message,
                confirmButtonColor: '#10b981',
                timer: 2000,
                timerProgressBar: true
            }).then(function() {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: LANG.alert_failed,
                text: data.message,
                confirmButtonColor: '#ef4444'
            });
        }
    })
    .catch(function(error) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_error,
            text: LANG.alert_error_occurred + error.message,
            confirmButtonColor: '#ef4444'
        });
    });
}

// ==================== MULTI-SLOT ODC FUNCTIONS ====================

// Variable untuk menyimpan data box saat ini
var currentOdcBoxCoordinates = null;
var currentOdcBoxData = null;

// Set mode box (new atau existing)
function setOdcBoxMode(mode) {
    var optionNew = document.getElementById('optionNewBox');
    var optionExisting = document.getElementById('optionAddSlot');
    var groupSelectBox = document.getElementById('odcGroupSelectBox');
    var sectionLokasi = document.getElementById('sectionOdcLokasi');
    var sectionFoto = document.getElementById('sectionAddOdcFoto');
    var inputIsNewBox = document.getElementById('odcIsNewBox');
    
    if (mode === 'new') {
        optionNew.classList.add('active');
        optionExisting.classList.remove('active');
        groupSelectBox.style.display = 'none';
        sectionLokasi.style.display = 'block';
        sectionFoto.style.display = 'block'; // Tampilkan section foto untuk Box Baru
        inputIsNewBox.value = 'yes';
        
        // Reset koordinat
        document.getElementById('odcCoordinates').value = window.pendingOdcCoordinates || '';
        document.getElementById('odcCoordinatesDisplay').value = window.pendingOdcCoordinates || '';
        document.getElementById('odcBoxId').value = '';
        
        // Update title
        document.getElementById('modalOdcTitle').textContent = LANG.modal_add_odc;
        document.getElementById('odcNameHelp').textContent = LANG.unique_name_odc;
    } else {
        optionNew.classList.remove('active');
        optionExisting.classList.add('active');
        groupSelectBox.style.display = 'block';
        sectionLokasi.style.display = 'none';
        sectionFoto.style.display = 'none'; // Sembunyikan section foto untuk Tambah Slot
        inputIsNewBox.value = 'no';
        
        // Reset foto preview
        hapusFotoOdcAddPreview();
        
        // Populate dropdown box existing
        populateExistingBoxDropdown();
        
        // Update title
        document.getElementById('modalOdcTitle').textContent = LANG.modal_add_odc_slot_title;
        document.getElementById('odcNameHelp').textContent = LANG.odc_name_help_new_slot;
    }
}

// Populate dropdown box existing
function populateExistingBoxDropdown() {
    var selectBox = document.getElementById('odcSelectBox');
    selectBox.innerHTML = '<option value="">-- {$lang.odc_select_box_option} --</option>';
    
    // Group ODC by koordinat dari networkData
    if (networkData.odcs) {
        networkData.odcs.forEach(function(odc) {
            var option = document.createElement('option');
            option.value = odc.coordinates;
            option.dataset.boxId = odc.box_id;
            option.dataset.address = odc.address || '';
            var slotText = odc.total_slots > 1 ? odc.total_slots + ' slot' : '1 slot';
            option.textContent = (odc.box_id || odc.name) + ' (' + slotText + ') - ' + (odc.address || 'Tanpa alamat');
            selectBox.appendChild(option);
        });
    }
}

// Event ketika pilih box existing
function onSelectExistingBox() {
    var selectBox = document.getElementById('odcSelectBox');
    var selectedOption = selectBox.options[selectBox.selectedIndex];
    
    if (selectBox.value) {
        document.getElementById('odcCoordinates').value = selectBox.value;
        document.getElementById('odcCoordinatesDisplay').value = selectBox.value;
        document.getElementById('odcBoxId').value = selectedOption.dataset.boxId || '';
        document.getElementById('odcAddress').value = selectedOption.dataset.address || '';
    }
}

// Tambah slot dari popup marker
function tambahSlotOdc(coordinates, address) {
    // Simpan koordinat
    window.pendingOdcCoordinates = coordinates;
    
    // Buka modal
    document.getElementById('modalOdcOverlay').classList.add('active');
    
    // Set ke mode existing
    setOdcBoxMode('existing');
    
    // Set koordinat dan alamat
    document.getElementById('odcCoordinates').value = coordinates;
    document.getElementById('odcCoordinatesDisplay').value = coordinates;
    document.getElementById('odcAddress').value = address || '';
    
    // Select box yang sesuai di dropdown
    var selectBox = document.getElementById('odcSelectBox');
    populateExistingBoxDropdown();
    
    // Cari dan select option dengan koordinat yang sama
    for (var i = 0; i < selectBox.options.length; i++) {
        if (selectBox.options[i].value === coordinates) {
            selectBox.selectedIndex = i;
            onSelectExistingBox();
            break;
        }
    }
    
    // Reset form fields lainnya
    document.getElementById('odcName').value = '';
    document.getElementById('odcTotalPorts').value = '8';
    document.getElementById('odcUsedPorts').value = '0';
    document.getElementById('odcParentType').value = '';
    toggleOdcParentSelect();
    updateOdcPortTersediaAdd();
    resetAddOdcPortFields();
}

// Tampilkan detail ODC Box (multi-slot view)
function tampilkanDetailOdcBox(coordinates) {
    currentOdcBoxCoordinates = coordinates;
    
    // Fetch data dari server
    Swal.fire({
        title: LANG.alert_loading,
        text: LANG.alert_loading_odc_data,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: function() { Swal.showLoading(); }
    });
    
    fetch(baseUrl + 'plugin/network_mapping/get-odc-by-coordinates', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ coordinates: coordinates })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        Swal.close();
        
        if (data.status === 'success') {
            currentOdcBoxData = data.data;
            renderOdcBoxDetail(data.data);
            document.getElementById('modalDetailOdcBoxOverlay').classList.add('active');
        } else {
            Swal.fire({ icon: 'error', title: LANG.alert_error, text: data.message });
        }
    })
    .catch(function(error) {
        Swal.close();
        Swal.fire({ icon: 'error', title: LANG.alert_error, text: LANG.alert_failed_fetch_data + error.message });
    });
}

// Render detail ODC Box
function renderOdcBoxDetail(boxData) {
    var slots = boxData.slots || [];
    
    // Hitung total
    var totalPorts = 0;
    var usedPorts = 0;
    slots.forEach(function(slot) {
        totalPorts += parseInt(slot.total_ports) || 0;
        usedPorts += parseInt(slot.used_ports) || 0;
    });
    var availablePorts = totalPorts - usedPorts;
    
    // Update info box
    document.getElementById('boxOdcAddress').textContent = slots[0]?.address || '-';
    document.getElementById('boxOdcCoordinates').textContent = boxData.coordinates;
    document.getElementById('boxOdcTotalSlots').textContent = slots.length;
    document.getElementById('boxOdcTotalPorts').textContent = totalPorts;
    document.getElementById('boxOdcUsedPorts').textContent = usedPorts;
    document.getElementById('boxOdcAvailablePorts').textContent = availablePorts;
    
    // Render slots accordion
    var container = document.getElementById('boxOdcSlotsContainer');
    container.innerHTML = '';
    
    slots.forEach(function(slot, index) {
        var slotAvailable = parseInt(slot.total_ports) - parseInt(slot.used_ports);
        var portsData = slot.ports_data || [];
        
        // Build parent info
        var parentInfo = '';
        if (slot.parent_type === 'olt' && slot.parent_olt_name) {
            parentInfo = '<div class="slot-parent-info"><i class="fa fa-plug"></i> ' + LANG.js_connected_to_olt + '<strong>' + slot.parent_olt_name + '</strong></div>';
        } else if (slot.parent_type === 'odc' && slot.parent_odc_name) {
            parentInfo = '<div class="slot-parent-info"><i class="fa fa-plug"></i> ' + LANG.js_connected_to_odc + '<strong>' + slot.parent_odc_name + '</strong></div>';
        }
        
        // Build ports list
        var portsHtml = '<div class="slot-ports-list">';
        
        for (var p = 1; p <= parseInt(slot.total_ports); p++) {
            // Cari port di ports_data
            var portData = null;
            if (Array.isArray(portsData)) {
                portData = portsData.find(function(pd) { 
                    // Handle format "Port 1" atau "1"
                    var portNum = String(pd.port).replace(/[^0-9]/g, '');
                    return parseInt(portNum) === p;
                });
            }
            
            var isUsed = portData ? true : false;
            var portLabel = portData ? (portData.label || '') : '';
            
            portsHtml += '<div class="slot-port-item ' + (isUsed ? 'used' : '') + '">';
            portsHtml += '<span class="slot-port-number">' + p + '</span>';
            portsHtml += '<span class="slot-port-label ' + (isUsed && portLabel ? '' : 'empty') + '">' + (isUsed ? (portLabel || LANG.js_port_in_use) : LANG.js_port_available_single) + '</span>';
            portsHtml += '</div>';
        }
        portsHtml += '</div>';
        
        // Build accordion item
        var accordionHtml = 
            '<div class="slot-accordion-item" data-slot-id="' + slot.id + '">' +
                '<div class="slot-accordion-header" onclick="toggleSlotAccordion(this)">' +
                    '<span class="slot-number-badge">' + (slot.slot_number || (index + 1)) + '</span>' +
                    '<div class="slot-info">' +
                        '<div class="slot-name">' + slot.name + '</div>' +
                        '<div class="slot-meta">' +
                            'Port: <span class="port-used">' + slot.used_ports + ' ' + LANG.js_port_used + '</span> / ' +
                            '<span class="port-available">' + slotAvailable + ' ' + LANG.js_port_available + '</span> ' + LANG.js_from + ' ' + slot.total_ports +
                        '</div>' +
                    '</div>' +
                    '<div class="slot-accordion-actions">' +
                        '<button type="button" class="btn-slot-action btn-slot-edit" onclick="event.stopPropagation(); editSlotOdc(' + slot.id + ')" title="' + LANG.btn_edit_slot + '">' +
                            '<i class="fa fa-pencil"></i>' +
                        '</button>' +
                        '<button type="button" class="btn-slot-action btn-slot-delete" onclick="event.stopPropagation(); hapusSlotOdc(' + slot.id + ', \'' + slot.name.replace(/'/g, "\\'") + '\')" title="' + LANG.btn_delete_slot + '">' +
                            '<i class="fa fa-trash"></i>' +
                        '</button>' +
                    '</div>' +
                    '<div class="slot-accordion-toggle"><i class="fa fa-chevron-down"></i></div>' +
                '</div>' +
                '<div class="slot-accordion-body">' +
                    parentInfo +
                    portsHtml +
                '</div>' +
            '</div>';
        
        container.innerHTML += accordionHtml;
    });
}

// Toggle slot accordion
function toggleSlotAccordion(header) {
    var body = header.nextElementSibling;
    var isActive = header.classList.contains('active');
    
    // Close all first
    document.querySelectorAll('.slot-accordion-header').forEach(function(h) {
        h.classList.remove('active');
    });
    document.querySelectorAll('.slot-accordion-body').forEach(function(b) {
        b.classList.remove('active');
    });
    
    // Toggle current
    if (!isActive) {
        header.classList.add('active');
        body.classList.add('active');
    }
}

// Edit single slot ODC
function editSlotOdc(slotId) {
    // Tutup modal box
    document.getElementById('modalDetailOdcBoxOverlay').classList.remove('active');
    
    // Cari data slot dari currentOdcBoxData
    var slot = null;
    if (currentOdcBoxData && currentOdcBoxData.slots) {
        slot = currentOdcBoxData.slots.find(function(s) { return s.id == slotId; });
    }
    
    if (!slot) {
        // Fallback ke networkData.odcs_all
        if (networkData.odcs_all) {
            slot = networkData.odcs_all.find(function(o) { return o.id == slotId; });
        }
    }
    
    if (slot) {
        // Isi form edit
        document.getElementById('detailOdcId').value = slot.id;
        document.getElementById('detailOdcName').value = slot.name || '';
        document.getElementById('detailOdcCoordinates').value = slot.coordinates || '';
        document.getElementById('detailOdcCoordinatesHidden').value = slot.coordinates || '';
        document.getElementById('detailOdcAddress').value = slot.address || '';
        document.getElementById('detailOdcDescription').value = slot.description || '';
        document.getElementById('detailOdcStatus').value = slot.status || 'Active';
        document.getElementById('detailOdcTotalPorts').value = slot.total_ports || 8;
        document.getElementById('detailOdcUsedPorts').value = slot.used_ports || 0;
        document.getElementById('detailOdcSlotNumber').value = slot.slot_number || 1;
        
        // Update slot badge
        document.getElementById('detailOdcSlotBadge').textContent = slot.slot_number || 1;
        
        // Parent connection
        document.getElementById('detailOdcParentType').value = slot.parent_type || '';
        toggleDetailOdcParentSelect();
        
        if (slot.parent_type === 'olt') {
            document.getElementById('detailOdcParentOlt').value = slot.parent_olt_id || '';
        } else if (slot.parent_type === 'odc') {
            document.getElementById('detailOdcParentOdcSelect').value = slot.parent_odc_id || '';
        }
        
        // Port tersedia
        updateDetailOdcPortTersedia();
        
        // Generate port fields
        window.currentOdcPortsData = slot.ports_data || [];
        generateDetailOdcPortFields(parseInt(slot.used_ports) || 0, parseInt(slot.total_ports) || 8);
        
        // Hitung isFirstSlot DULU sebelum digunakan
        var slotNumber = parseInt(slot.slot_number) || 1;
        var isFirstSlot = true;
        var minSlotNumber = slotNumber;
        
        if (currentOdcBoxData && currentOdcBoxData.slots && currentOdcBoxData.slots.length > 0) {
            minSlotNumber = Math.min.apply(Math, currentOdcBoxData.slots.map(function(s) {
                return parseInt(s.slot_number) || 1;
            }));
            isFirstSlot = (slotNumber === minSlotNumber);
        }
        
        // Show/hide lokasi section (hanya untuk slot dengan slot_number paling kecil)
        var sectionLokasi = document.getElementById('sectionDetailOdcLokasi');
        if (isFirstSlot) {
            sectionLokasi.style.display = 'block';
        } else {
            sectionLokasi.style.display = 'none';
        }
        
        // Section Foto - hanya tampil untuk slot dengan slot_number paling kecil di box
        var sectionFoto = document.getElementById('sectionDetailOdcFoto');
        
        if (isFirstSlot) {
            sectionFoto.style.display = 'block';
            
            // Reset foto state
            document.getElementById('editOdcHapusFoto').value = '0';
            document.getElementById('editOdcFoto').value = '';
            
            // Tampilkan foto existing jika ada
            var fotoPlaceholder = document.getElementById('editOdcFotoPlaceholder');
            var fotoPreview = document.getElementById('editOdcFotoPreview');
            var fotoPreviewImg = document.getElementById('editOdcFotoPreviewImg');
            
            // Cari foto dari slot pertama (slot dengan slot_number paling kecil)
            var fotoSlot = null;
            if (currentOdcBoxData && currentOdcBoxData.slots) {
                fotoSlot = currentOdcBoxData.slots.find(function(s) {
                    return parseInt(s.slot_number) === minSlotNumber;
                });
            }
            
            if (fotoSlot && fotoSlot.foto && fotoSlot.foto_url) {
                fotoPreviewImg.src = fotoSlot.foto_url;
                fotoPlaceholder.style.display = 'none';
                fotoPreview.style.display = 'block';
            } else if (slot.foto && slot.foto_url) {
                fotoPreviewImg.src = slot.foto_url;
                fotoPlaceholder.style.display = 'none';
                fotoPreview.style.display = 'block';
            } else {
                fotoPlaceholder.style.display = 'flex';
                fotoPreview.style.display = 'none';
                fotoPreviewImg.src = '';
            }
        } else {
            // Sembunyikan section foto untuk slot selain slot pertama
            sectionFoto.style.display = 'none';
        }
        
        // Simpan data untuk referensi
        window.currentDetailOdcId = slot.id;
        window.currentDetailOdcData = slot;
        
        // Buka modal edit slot
        document.getElementById('modalDetailOdcOverlay').classList.add('active');
    } else {
        Swal.fire({ icon: 'error', title: LANG.alert_error, text: LANG.alert_slot_data_not_found });
    }
}

// Hapus single slot ODC
function hapusSlotOdc(slotId, slotName) {
    Swal.fire({
        title: LANG.alert_delete_odc_slot_title,
        html: LANG.alert_delete_odc_slot_text_prefix + '<strong>' + slotName + '</strong>' + LANG.alert_delete_odc_slot_text_suffix,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: LANG.alert_yes_delete,
        cancelButtonText: LANG.alert_cancel,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280'
    }).then(function(result) {
        if (result.isConfirmed) {
            Swal.fire({
                title: LANG.alert_deleting,
                text: LANG.alert_please_wait,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: function() { Swal.showLoading(); }
            });
            
            fetch(baseUrl + 'plugin/network_mapping/delete-odc', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ odc_id: slotId })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: LANG.alert_success,
                        text: data.message,
                        confirmButtonColor: '#10b981'
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: LANG.alert_failed, text: data.message });
                }
            })
            .catch(function(error) {
                Swal.fire({ icon: 'error', title: LANG.alert_error, text: error.message });
            });
        }
    });
}

// Tambah slot dari modal detail box
function tambahSlotDariDetail() {
    if (currentOdcBoxCoordinates) {
        // Tutup modal detail
        document.getElementById('modalDetailOdcBoxOverlay').classList.remove('active');
        
        // Buka modal tambah dengan mode existing
        var address = currentOdcBoxData?.slots?.[0]?.address || '';
        tambahSlotOdc(currentOdcBoxCoordinates, address);
    }
}

// Tutup modal detail ODC Box
function tutupModalDetailOdcBox() {
    document.getElementById('modalDetailOdcBoxOverlay').classList.remove('active');
    currentOdcBoxCoordinates = null;
    currentOdcBoxData = null;
}

// Override fungsi bukaModalOdc untuk support multi-slot
var originalBukaModalOdc = bukaModalOdc;
function bukaModalOdc(coordinates) {
    // Simpan koordinat pending
    window.pendingOdcCoordinates = coordinates;
    
    // Reset ke mode new box
    setOdcBoxMode('new');
    
    // Set koordinat
    document.getElementById('odcCoordinates').value = coordinates;
    document.getElementById('odcCoordinatesDisplay').value = coordinates;
    
    // Reset form
    document.getElementById('formOdc').reset();
    document.getElementById('odcCoordinates').value = coordinates;
    document.getElementById('odcCoordinatesDisplay').value = coordinates;
    document.getElementById('odcTotalPorts').value = '8';
    document.getElementById('odcUsedPorts').value = '0';
    document.getElementById('odcIsNewBox').value = 'yes';
    
    updateOdcPortTersediaAdd();
    resetAddOdcPortFields();
    
    // Buka modal
    document.getElementById('modalOdcOverlay').classList.add('active');
}

// ==================== FUNGSI DETAIL/EDIT ODC ====================

// Fungsi untuk menampilkan modal detail ODC
function tampilkanDetailOdc(id) {
    var odc = null;
    if (networkData.odcs) {
        odc = networkData.odcs.find(function(o) {
            return o.id == id;
        });
    }
    
    if (odc) {
        document.getElementById('detailOdcId').value = id;
        window.currentDetailOdcId = id;
        window.currentDetailOdcData = odc;
        
        // Isi data ke modal
        document.getElementById('detailOdcName').value = odc.name || '';
        document.getElementById('detailOdcAddress').value = odc.address || '';
        document.getElementById('detailOdcDescription').value = odc.description || '';
        
        // Status
        var statusSelect = document.getElementById('detailOdcStatus');
        statusSelect.value = odc.status || 'Active';
        
        // Coordinates
        document.getElementById('detailOdcCoordinates').value = odc.coordinates || '';
        document.getElementById('detailOdcCoordinatesHidden').value = odc.coordinates || '';
        
        // Parent Connection
        var parentTypeSelect = document.getElementById('detailOdcParentType');
        var detailOdcGroupParentOlt = document.getElementById('detailOdcGroupParentOlt');
        var detailOdcGroupParentOdc = document.getElementById('detailOdcGroupParentOdc');
        
        parentTypeSelect.value = odc.parent_type || '';
        
        window.currentOdcParentType = odc.parent_type || '';
        window.currentOdcParentOltId = odc.parent_olt_id || '';
        window.currentOdcParentOdcId = odc.parent_odc_id || '';
        
        detailOdcGroupParentOlt.style.display = 'none';
        detailOdcGroupParentOdc.style.display = 'none';
        
        if (odc.parent_type === 'olt') {
            detailOdcGroupParentOlt.style.display = 'block';
            document.getElementById('detailOdcParentOlt').value = odc.parent_olt_id || '';
        } else if (odc.parent_type === 'odc') {
            detailOdcGroupParentOdc.style.display = 'block';
            document.getElementById('detailOdcParentOdcSelect').value = odc.parent_odc_id || '';
        }
        
        // Port Configuration
        document.getElementById('detailOdcTotalPorts').value = odc.total_ports || 8;
        document.getElementById('detailOdcUsedPorts').value = odc.used_ports || 0;
        
        updateDetailOdcPortTersedia();
        
        window.currentOdcPortsData = odc.ports_data || [];
        
        generateDetailOdcPortFields(parseInt(odc.used_ports) || 0, parseInt(odc.total_ports) || 8);
        
        // Section Foto - hanya tampil untuk Slot 1
        var sectionFoto = document.getElementById('sectionDetailOdcFoto');
        var slotNumber = odc.slot_number || 1;
        
        if (slotNumber == 1) {
            sectionFoto.style.display = 'block';
            
            // Reset foto state
            document.getElementById('editOdcHapusFoto').value = '0';
            document.getElementById('editOdcFoto').value = '';
            
            // Tampilkan foto existing jika ada
            var fotoPlaceholder = document.getElementById('editOdcFotoPlaceholder');
            var fotoPreview = document.getElementById('editOdcFotoPreview');
            var fotoPreviewImg = document.getElementById('editOdcFotoPreviewImg');
            
            if (odc.foto && odc.foto_url) {
                fotoPreviewImg.src = odc.foto_url;
                fotoPlaceholder.style.display = 'none';
                fotoPreview.style.display = 'block';
            } else {
                fotoPlaceholder.style.display = 'flex';
                fotoPreview.style.display = 'none';
                fotoPreviewImg.src = '';
            }
        } else {
            // Sembunyikan section foto untuk slot selain Slot 1
            sectionFoto.style.display = 'none';
        }
        
        document.getElementById('modalDetailOdcOverlay').classList.add('active');
    } else {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_odc_not_found,
            text: LANG.alert_odc_not_found_text
        });
    }
}

// Toggle parent select di modal detail ODC
function toggleDetailOdcParentSelect() {
    var parentType = document.getElementById('detailOdcParentType').value;
    var groupOlt = document.getElementById('detailOdcGroupParentOlt');
    var groupOdc = document.getElementById('detailOdcGroupParentOdc');
    
    groupOlt.style.display = 'none';
    groupOdc.style.display = 'none';
    
    if (parentType === 'olt') {
        groupOlt.style.display = 'block';
        if (window.currentOdcParentType !== 'olt') {
            document.getElementById('detailOdcParentOlt').value = '';
        }
    } else if (parentType === 'odc') {
        groupOdc.style.display = 'block';
        if (window.currentOdcParentType !== 'odc') {
            document.getElementById('detailOdcParentOdcSelect').value = '';
        }
    }
}

// ==================== FUNGSI MULTI-PORT DETAIL ODC ====================

// Fungsi untuk ubah port di modal detail ODC
function ubahDetailOdcPort(tipe, delta) {
    var inputId = tipe === 'total' ? 'detailOdcTotalPorts' : 'detailOdcUsedPorts';
    var input = document.getElementById(inputId);
    var nilai = parseInt(input.value) || 0;
    
    var totalPorts = parseInt(document.getElementById('detailOdcTotalPorts').value) || 8;
    var usedPorts = parseInt(document.getElementById('detailOdcUsedPorts').value) || 0;
    
    if (tipe === 'total') {
        nilai += delta;
        if (nilai < 1) nilai = 1;
        if (nilai > 128) nilai = 128;
        if (nilai < usedPorts) nilai = usedPorts;
        input.value = nilai;
        
        updateOdcPortsDataFromFields();
        generateDetailOdcPortFields(usedPorts, nilai);
    } else {
        nilai += delta;
        if (nilai < 0) nilai = 0;
        if (nilai > totalPorts) nilai = totalPorts;
        input.value = nilai;
        
        generateDetailOdcPortFields(nilai, totalPorts);
    }
    
    updateDetailOdcPortTersedia();
}

// Fungsi untuk update tampilan port tersedia di modal detail ODC
function updateDetailOdcPortTersedia() {
    var totalPorts = parseInt(document.getElementById('detailOdcTotalPorts').value) || 0;
    var usedPorts = parseInt(document.getElementById('detailOdcUsedPorts').value) || 0;
    var tersedia = totalPorts - usedPorts;
    
    var portTersediaElement = document.getElementById('detailOdcPortTersedia');
    if (portTersediaElement) {
        portTersediaElement.textContent = tersedia;
        
        var portInfoSimple = portTersediaElement.closest('.port-info-simple');
        if (portInfoSimple) {
            if (tersedia === 0) {
                portInfoSimple.style.background = '#fee2e2';
                portInfoSimple.style.borderColor = '#fca5a5';
                portInfoSimple.style.color = '#7f1d1d';
                portInfoSimple.querySelector('strong').style.color = '#991b1b';
            } else if (tersedia <= totalPorts * 0.2) {
                portInfoSimple.style.background = '#fef3c7';
                portInfoSimple.style.borderColor = '#fde047';
                portInfoSimple.style.color = '#713f12';
                portInfoSimple.querySelector('strong').style.color = '#854d0e';
            } else {
                portInfoSimple.style.background = '#ecfdf5';
                portInfoSimple.style.borderColor = '#a7f3d0';
                portInfoSimple.style.color = '#065f46';
                portInfoSimple.querySelector('strong').style.color = '#047857';
            }
        }
    }
}

// Fungsi untuk generate multi-port fields di modal detail ODC
function generateDetailOdcPortFields(usedPorts, totalPorts) {
    var container = document.getElementById('detailOdcPortsContainer');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (usedPorts <= 0) {
        container.innerHTML = '<p class="text-muted" style="text-align: center; padding: 10px;"><i class="fa fa-info-circle"></i> ' + LANG.js_no_ports_used + '</p>';
        return;
    }
    
    var headerHtml = '<div class="ports-fields-header" style="background: #ecfdf5; padding: 10px; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid #10b981;">' +
        '<strong style="color: #065f46;"><i class="fa fa-plug"></i> ' + LANG.js_detail + ' ' + usedPorts + ' ' + LANG.js_ports_used + '</strong>' +
        '</div>';
    container.innerHTML = headerHtml;
    
    for (var i = 0; i < usedPorts; i++) {
        var portIndex = i + 1;
        
        var existingPort = '';
        var existingLabel = '';
        if (window.currentOdcPortsData && window.currentOdcPortsData[i]) {
            existingPort = window.currentOdcPortsData[i].port || '';
            existingLabel = window.currentOdcPortsData[i].label || '';
        }
        
        var fieldHtml = '<div class="port-field-item" style="background: #fafafa; padding: 12px; border-radius: 6px; margin-bottom: 10px; border: 1px solid #e5e7eb;">' +
            '<div style="font-weight: 600; margin-bottom: 8px; color: #4b5563;"><i class="fa fa-circle" style="font-size: 8px; color: #10b981;"></i> ' + LANG.js_port_number + portIndex + '</div>' +
            '<div class="row">' +
                '<div class="col-md-6">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.js_port_number_label + '</label>' +
                        '<select class="form-control input-sm" id="detailOdcPortSelect_' + i + '" onchange="onDetailOdcPortSelectChange(' + i + ')">' +
                            generateDetailOdcPortOptionsHtml(totalPorts, existingPort) +
                        '</select>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.js_port_label_label + '</label>' +
                        '<input type="text" class="form-control input-sm" id="detailOdcPortLabel_' + i + '" value="' + escapeHtml(existingLabel) + '" placeholder="' + LANG.js_port_label_placeholder_odc + '" onchange="updateOdcPortsDataFromFields()">' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        container.innerHTML += fieldHtml;
    }
    
    updateAllDetailOdcPortDropdownsDisabledState();
}

// Fungsi untuk generate options Port di modal detail ODC
function generateDetailOdcPortOptionsHtml(totalPorts, selectedValue) {
    var html = '<option value="">' + LANG.js_select_port + '</option>';
    for (var i = 1; i <= totalPorts; i++) {
        var portValue = 'Port ' + i;
        var selected = (portValue === selectedValue) ? 'selected' : '';
        html += '<option value="' + portValue + '" ' + selected + '>' + portValue + '</option>';
    }
    return html;
}

// Fungsi yang dipanggil ketika Port dropdown berubah di modal detail ODC
function onDetailOdcPortSelectChange(changedIndex) {
    updateOdcPortsDataFromFields();
    updateAllDetailOdcPortDropdownsDisabledState();
}

// Fungsi untuk update disabled state semua dropdown Port di modal detail ODC
function updateAllDetailOdcPortDropdownsDisabledState() {
    var usedPorts = parseInt(document.getElementById('detailOdcUsedPorts').value) || 0;
    
    var selectedPorts = [];
    for (var i = 0; i < usedPorts; i++) {
        var select = document.getElementById('detailOdcPortSelect_' + i);
        if (select && select.value) {
            selectedPorts.push({
                index: i,
                value: select.value
            });
        }
    }
    
    for (var i = 0; i < usedPorts; i++) {
        var select = document.getElementById('detailOdcPortSelect_' + i);
        if (!select) continue;
        
        var currentValue = select.value;
        var options = select.querySelectorAll('option');
        
        options.forEach(function(option) {
            if (!option.value) {
                option.disabled = false;
                option.style.color = '';
                return;
            }
            
            var isUsedByOther = selectedPorts.some(function(item) {
                return item.value === option.value && item.index !== i;
            });
            
            if (isUsedByOther) {
                option.disabled = true;
                option.style.color = '#999';
            } else {
                option.disabled = false;
                option.style.color = '';
            }
        });
    }
}

// Fungsi untuk update ports data dari fields ke window variable di modal detail ODC
function updateOdcPortsDataFromFields() {
    var usedPorts = parseInt(document.getElementById('detailOdcUsedPorts').value) || 0;
    var portsData = [];
    
    for (var i = 0; i < usedPorts; i++) {
        var portSelect = document.getElementById('detailOdcPortSelect_' + i);
        var labelInput = document.getElementById('detailOdcPortLabel_' + i);
        
        if (portSelect && labelInput) {
            portsData.push({
                port: portSelect.value,
                label: labelInput.value
            });
        }
    }
    
    window.currentOdcPortsData = portsData;
}

// Fungsi untuk mendapatkan ports data sebagai JSON string di modal detail ODC
function getOdcPortsDataJson() {
    updateOdcPortsDataFromFields();
    return JSON.stringify(window.currentOdcPortsData || []);
}

// ==================== END FUNGSI MULTI-PORT DETAIL ODC ====================

// Fungsi untuk menutup modal detail ODC
function tutupModalDetailOdc() {
    document.getElementById('modalDetailOdcOverlay').classList.remove('active');
    window.currentDetailOdcId = null;
    window.currentDetailOdcData = null;
    
    window.remapOdcId = null;
    window.remapOdcData = null;
    
    if (isPickMode && pickModeType === 'remap-odc') {
        batalkanModePick();
    }
    
    if (tempMarker) {
        map.removeLayer(tempMarker);
        tempMarker = null;
    }
}

// Fungsi untuk mengaktifkan mode remap koordinat ODC
function aktifkanModeRemapOdc() {
    window.remapOdcId = document.getElementById('detailOdcId').value;
    
    updateOdcPortsDataFromFields();
    
    window.remapOdcData = {
        name: document.getElementById('detailOdcName').value,
        status: document.getElementById('detailOdcStatus').value,
        address: document.getElementById('detailOdcAddress').value,
        description: document.getElementById('detailOdcDescription').value,
        parent_type: document.getElementById('detailOdcParentType').value,
        parent_olt_id: document.getElementById('detailOdcParentOlt').value,
        parent_odc_id: document.getElementById('detailOdcParentOdcSelect').value,
        total_ports: document.getElementById('detailOdcTotalPorts').value,
        used_ports: document.getElementById('detailOdcUsedPorts').value,
        ports_data: window.currentOdcPortsData || []
    };
    
    document.getElementById('modalDetailOdcOverlay').classList.remove('active');
    
    isPickMode = true;
    pickModeType = 'remap-odc';
    
    var pickIndicator = document.getElementById('pickModeIndicator');
    var pickText = pickIndicator.querySelector('span');
    pickText.textContent = LANG.js_click_map_change_odc_location;
    pickIndicator.classList.add('active');
    
    document.getElementById('map').style.cursor = 'crosshair';
    
    setTimeout(function() {
        map.on('click', onMapClickPick);
    }, 300);
}

// Fungsi untuk buka kembali modal detail ODC setelah pilih lokasi baru
function bukaKembaliModalDetailOdc(newCoordinates) {
    var odc = null;
    if (networkData.odcs && window.remapOdcId) {
        odc = networkData.odcs.find(function(o) {
            return o.id == window.remapOdcId;
        });
    }
    
    if (odc && window.remapOdcData) {
        document.getElementById('detailOdcId').value = window.remapOdcId;
        document.getElementById('detailOdcName').value = window.remapOdcData.name;
        document.getElementById('detailOdcStatus').value = window.remapOdcData.status;
        document.getElementById('detailOdcAddress').value = window.remapOdcData.address;
        document.getElementById('detailOdcDescription').value = window.remapOdcData.description;
        
        document.getElementById('detailOdcCoordinates').value = newCoordinates;
        document.getElementById('detailOdcCoordinatesHidden').value = newCoordinates;
        
        // Restore parent connection
        var parentTypeSelect = document.getElementById('detailOdcParentType');
        var detailOdcGroupParentOlt = document.getElementById('detailOdcGroupParentOlt');
        var detailOdcGroupParentOdc = document.getElementById('detailOdcGroupParentOdc');
        
        var savedParentType = window.remapOdcData.parent_type || odc.parent_type || '';
        parentTypeSelect.value = savedParentType;
        
        detailOdcGroupParentOlt.style.display = 'none';
        detailOdcGroupParentOdc.style.display = 'none';
        
        if (savedParentType === 'olt') {
            detailOdcGroupParentOlt.style.display = 'block';
            document.getElementById('detailOdcParentOlt').value = window.remapOdcData.parent_olt_id || odc.parent_olt_id || '';
        } else if (savedParentType === 'odc') {
            detailOdcGroupParentOdc.style.display = 'block';
            document.getElementById('detailOdcParentOdcSelect').value = window.remapOdcData.parent_odc_id || odc.parent_odc_id || '';
        }
        
        // Restore port configuration
        var savedTotalPorts = window.remapOdcData.total_ports || odc.total_ports || 8;
        var savedUsedPorts = window.remapOdcData.used_ports || odc.used_ports || 0;
        
        document.getElementById('detailOdcTotalPorts').value = savedTotalPorts;
        document.getElementById('detailOdcUsedPorts').value = savedUsedPorts;
        
        updateDetailOdcPortTersedia();
        
        window.currentOdcPortsData = window.remapOdcData.ports_data || odc.ports_data || [];
        
        generateDetailOdcPortFields(parseInt(savedUsedPorts), parseInt(savedTotalPorts));
        
        window.currentDetailOdcId = window.remapOdcId;
        window.currentDetailOdcData = odc;
        
        document.getElementById('modalDetailOdcOverlay').classList.add('active');
        
        Swal.fire({
            icon: 'success',
            title: LANG.alert_location_changed,
            text: LANG.alert_location_changed_text,
            timer: 2000,
            showConfirmButton: false,
            customClass: {
                container: 'swal-high-zindex'
            }
        });
        
        window.remapOdcId = null;
        window.remapOdcData = null;
    }
}

// Fungsi untuk simpan edit ODC
function simpanEditOdc() {
    var odc_id = document.getElementById('detailOdcId').value;
    var name = document.getElementById('detailOdcName').value.trim();
    var status = document.getElementById('detailOdcStatus').value;
    var coordinates = document.getElementById('detailOdcCoordinatesHidden').value;
    var address = document.getElementById('detailOdcAddress').value.trim();
    var description = document.getElementById('detailOdcDescription').value.trim();
    
    var parent_type = document.getElementById('detailOdcParentType').value;
    var parent_olt_id = '';
    var parent_odc_id = '';
    
    if (parent_type === 'olt') {
        parent_olt_id = document.getElementById('detailOdcParentOlt').value;
    } else if (parent_type === 'odc') {
        parent_odc_id = document.getElementById('detailOdcParentOdcSelect').value;
    }
    
    var total_ports = document.getElementById('detailOdcTotalPorts').value;
    var used_ports = document.getElementById('detailOdcUsedPorts').value;
    var ports_data = getOdcPortsDataJson();
    
    var hapusFoto = document.getElementById('editOdcHapusFoto').value;
    var fotoInput = document.getElementById('editOdcFoto');
    
    // Validasi
    if (!name) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_validation_failed,
            text: LANG.alert_odc_name_required
        });
        return;
    }
    
    if (!coordinates) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_validation_failed,
            text: LANG.alert_coordinates_required
        });
        return;
    }
    
    if (parent_type === 'olt' && !parent_olt_id) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_validation_failed,
            text: LANG.alert_select_olt_parent
        });
        return;
    }
    
    if (parent_type === 'odc' && !parent_odc_id) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_validation_failed,
            text: LANG.alert_select_odc_parent
        });
        return;
    }
    
    // Validasi ukuran foto (max 5MB)
    if (fotoInput && fotoInput.files.length > 0) {
        var fileSize = fotoInput.files[0].size;
        var maxSize = 5 * 1024 * 1024; // 5MB
        if (fileSize > maxSize) {
            Swal.fire({
                icon: 'error',
                title: LANG.alert_validation_failed,
                text: LANG.alert_photo_max_5mb
            });
            return;
        }
    }
    
    Swal.fire({
        title: LANG.alert_save_changes_odc_title,
        text: LANG.alert_save_changes_odc_text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-save"></i> ' + LANG.alert_yes_save,
        cancelButtonText: LANG.alert_cancel,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: LANG.alert_saving,
                text: LANG.alert_please_wait,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Gunakan FormData untuk upload file
            var formData = new FormData();
            formData.append('odc_id', odc_id);
            formData.append('name', name);
            formData.append('status', status);
            formData.append('coordinates', coordinates);
            formData.append('address', address);
            formData.append('description', description);
            formData.append('parent_type', parent_type);
            formData.append('parent_olt_id', parent_olt_id);
            formData.append('parent_odc_id', parent_odc_id);
            formData.append('total_ports', total_ports);
            formData.append('used_ports', used_ports);
            formData.append('ports_data', ports_data);
            formData.append('hapus_foto', hapusFoto);
            
            // Tambahkan foto jika ada
            if (fotoInput && fotoInput.files.length > 0) {
                formData.append('foto_odc', fotoInput.files[0]);
            }
            
            fetch(baseUrl + 'plugin/network_mapping/update-odc', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: LANG.alert_success,
                        text: LANG.alert_odc_changes_saved,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: LANG.alert_failed,
                        text: data.message || LANG.alert_save_error
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: LANG.alert_error,
                    text: LANG.alert_server_connection_failed
                });
            });
        }
    });
}

// Fungsi untuk hapus ODC
function hapusOdc() {
    var odc_id = document.getElementById('detailOdcId').value;
    var odc_name = document.getElementById('detailOdcName').value;
    
    if (!odc_id) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_error,
            text: LANG.alert_odc_id_not_found
        });
        return;
    }
    
    Swal.fire({
        title: LANG.alert_delete_odc_title,
        text: LANG.alert_delete_odc_prefix + ' "' + odc_name + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-trash"></i> ' + LANG.alert_yes_delete,
        cancelButtonText: LANG.alert_cancel,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: LANG.alert_deleting,
                text: LANG.alert_please_wait,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(baseUrl + 'plugin/network_mapping/delete-odc', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    odc_id: odc_id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: LANG.alert_success,
                        text: LANG.alert_odc_deleted_success,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: LANG.alert_failed,
                        text: data.message || LANG.alert_odc_delete_error
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: LANG.alert_error,
                    text: LANG.alert_server_connection_failed
                });
            });
        }
    });
}

// Fungsi untuk pusatkan peta ke ODC dari modal detail
function pusatkanPetaKeOdcDetail() {
    if (window.currentDetailOdcId) {
        var odc = networkData.odcs.find(function(o) { return o.id == window.currentDetailOdcId; });
        if (odc) {
            var coords = odc.coordinates.split(',').map(parseFloat);
            map.setView(coords, 18);
            tutupModalDetailOdc();
        }
    }
}

// ==================== END FUNGSI ODC ====================

// ==================== FUNGSI ODP ====================

// Fungsi untuk buka modal ODP
function bukaModalOdp(coordinates) {
    document.getElementById('odpCoordinates').value = coordinates;
    document.getElementById('odpCoordinatesDisplay').value = coordinates;
    document.getElementById('modalOdpOverlay').classList.add('active');
    
    // Reset parent selection
    document.getElementById('odpParentType').value = '';
    toggleOdpParentSelect();
    
    // Reset dan inisialisasi multi-port fields
    resetAddOdpPortFields();
    updateOdpPortTersediaAdd();
}

// Fungsi untuk tutup modal ODP
function tutupModalOdp() {
    document.getElementById('modalOdpOverlay').classList.remove('active');
    document.getElementById('formOdp').reset();
    document.getElementById('odpTotalPorts').value = 8;
    document.getElementById('odpUsedPorts').value = 0;
    
    // Reset parent selection dan port detail
    document.getElementById('odpParentType').value = '';
    toggleOdpParentSelect();
    
    // Reset multi-port fields
    resetAddOdpPortFields();
    updateOdpPortTersediaAdd();
    
    // Reset foto preview
    hapusFotoOdpAddPreview();
    
    // Reset mode ke Box Baru (default)
    setOdpBoxMode('new');
    
    // Hapus temp marker
    if (tempMarker) {
        map.removeLayer(tempMarker);
        tempMarker = null;
    }
}

// Toggle tampilan dropdown parent ODP
function toggleOdpParentSelect() {
    var parentType = document.getElementById('odpParentType').value;
    var groupOdc = document.getElementById('odpGroupParentOdc');
    var groupOdp = document.getElementById('odpGroupParentOdp');
    var selectOdp = document.getElementById('odpParentOdp');
    var helpNoOdp = document.getElementById('odpHelpNoOdp');
    
    // Reset visibility
    groupOdc.style.display = 'none';
    groupOdp.style.display = 'none';
    document.getElementById('odpParentOdc').value = '';
    document.getElementById('odpParentOdp').value = '';
    
    if (parentType === 'odc') {
        groupOdc.style.display = 'block';
    } else if (parentType === 'odp') {
        groupOdp.style.display = 'block';
        
        // Cek apakah ada ODP lain
        var odpOptions = selectOdp.querySelectorAll('option');
        if (odpOptions.length <= 1) {
            selectOdp.disabled = true;
            helpNoOdp.style.display = 'block';
        } else {
            selectOdp.disabled = false;
            helpNoOdp.style.display = 'none';
        }
    }
}

// ==================== FUNGSI MULTI-PORT ADD ODP ====================

// Fungsi untuk ubah port di modal Add ODP dengan tombol +/-
function ubahOdpPortAdd(tipe, delta) {
    var inputId = tipe === 'total' ? 'odpTotalPorts' : 'odpUsedPorts';
    var input = document.getElementById(inputId);
    var nilai = parseInt(input.value) || 0;
    
    var totalPorts = parseInt(document.getElementById('odpTotalPorts').value) || 8;
    var usedPorts = parseInt(document.getElementById('odpUsedPorts').value) || 0;
    
    if (tipe === 'total') {
        nilai += delta;
        if (nilai < 1) nilai = 1;
        if (nilai > 128) nilai = 128;
        if (nilai < usedPorts) nilai = usedPorts;
        input.value = nilai;
        
        updateAddOdpPortsDataFromFields();
        generateAddOdpPortFields(usedPorts, nilai);
    } else {
        nilai += delta;
        if (nilai < 0) nilai = 0;
        if (nilai > totalPorts) nilai = totalPorts;
        input.value = nilai;
        
        generateAddOdpPortFields(nilai, totalPorts);
    }
    
    updateOdpPortTersediaAdd();
}

// Fungsi untuk update tampilan port tersedia di modal Add ODP
function updateOdpPortTersediaAdd() {
    var totalPorts = parseInt(document.getElementById('odpTotalPorts').value) || 0;
    var usedPorts = parseInt(document.getElementById('odpUsedPorts').value) || 0;
    var tersedia = totalPorts - usedPorts;
    
    var portTersediaElement = document.getElementById('odpPortTersedia');
    if (portTersediaElement) {
        portTersediaElement.textContent = tersedia;
        
        var portInfoSimple = portTersediaElement.closest('.port-info-simple');
        if (portInfoSimple) {
            if (tersedia === 0) {
                portInfoSimple.style.background = '#fee2e2';
                portInfoSimple.style.borderColor = '#fca5a5';
                portInfoSimple.style.color = '#7f1d1d';
                portInfoSimple.querySelector('strong').style.color = '#991b1b';
            } else if (tersedia <= totalPorts * 0.2) {
                portInfoSimple.style.background = '#fef3c7';
                portInfoSimple.style.borderColor = '#fde047';
                portInfoSimple.style.color = '#713f12';
                portInfoSimple.querySelector('strong').style.color = '#854d0e';
            } else {
                portInfoSimple.style.background = '#fefce8';
                portInfoSimple.style.borderColor = '#fde047';
                portInfoSimple.style.color = '#713f12';
                portInfoSimple.querySelector('strong').style.color = '#ca8a04';
            }
        }
    }
}

// Fungsi untuk generate multi-port fields di modal Add ODP
function generateAddOdpPortFields(usedPorts, totalPorts) {
    var container = document.getElementById('addOdpPortsContainer');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (usedPorts <= 0) {
        container.innerHTML = '<p class="text-muted" style="text-align: center; padding: 10px;"><i class="fa fa-info-circle"></i> ' + LANG.js_no_ports_used + '</p>';
        return;
    }
    
    var headerHtml = '<div class="ports-fields-header" style="background: #fefce8; padding: 10px; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid #f59e0b;">' +
        '<strong style="color: #92400e;"><i class="fa fa-plug"></i> ' + LANG.js_detail + ' ' + usedPorts + ' ' + LANG.js_ports_used + '</strong>' +
        '</div>';
    container.innerHTML = headerHtml;
    
    for (var i = 0; i < usedPorts; i++) {
        var portIndex = i + 1;
        
        var existingPort = '';
        var existingLabel = '';
        if (window.addOdpPortsData && window.addOdpPortsData[i]) {
            existingPort = window.addOdpPortsData[i].port || '';
            existingLabel = window.addOdpPortsData[i].label || '';
        }
        
        var fieldHtml = '<div class="port-field-item" style="background: #fafafa; padding: 12px; border-radius: 6px; margin-bottom: 10px; border: 1px solid #e5e7eb;">' +
            '<div style="font-weight: 600; margin-bottom: 8px; color: #4b5563;"><i class="fa fa-circle" style="font-size: 8px; color: #f59e0b;"></i> ' + LANG.js_port_number + portIndex + '</div>' +
            '<div class="row">' +
                '<div class="col-md-6">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.js_port_number_label + '</label>' +
                        '<select class="form-control input-sm" id="addOdpPortSelect_' + i + '" onchange="onAddOdpPortSelectChange(' + i + ')">' +
                            generateAddOdpPortOptionsHtml(totalPorts, existingPort) +
                        '</select>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.js_port_label_label + '</label>' +
                        '<input type="text" class="form-control input-sm" id="addOdpPortLabel_' + i + '" value="' + escapeHtml(existingLabel) + '" placeholder="' + LANG.js_port_label_placeholder_odp + '" onchange="updateAddOdpPortsDataFromFields()">' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        container.innerHTML += fieldHtml;
    }
    
    updateAllAddOdpPortDropdownsDisabledState();
}

// Fungsi untuk generate options Port di modal Add ODP
function generateAddOdpPortOptionsHtml(totalPorts, selectedValue) {
    var html = '<option value="">' + LANG.js_select_port + '</option>';
    for (var i = 1; i <= totalPorts; i++) {
        var portValue = 'Port ' + i;
        var selected = (portValue === selectedValue) ? 'selected' : '';
        html += '<option value="' + portValue + '" ' + selected + '>' + portValue + '</option>';
    }
    return html;
}

// Fungsi yang dipanggil ketika Port dropdown berubah di modal Add ODP
function onAddOdpPortSelectChange(changedIndex) {
    updateAddOdpPortsDataFromFields();
    updateAllAddOdpPortDropdownsDisabledState();
}

// Fungsi untuk update disabled state semua dropdown Port di modal Add ODP
function updateAllAddOdpPortDropdownsDisabledState() {
    var usedPorts = parseInt(document.getElementById('odpUsedPorts').value) || 0;
    
    var selectedPorts = [];
    for (var i = 0; i < usedPorts; i++) {
        var select = document.getElementById('addOdpPortSelect_' + i);
        if (select && select.value) {
            selectedPorts.push({
                index: i,
                value: select.value
            });
        }
    }
    
    for (var i = 0; i < usedPorts; i++) {
        var select = document.getElementById('addOdpPortSelect_' + i);
        if (!select) continue;
        
        var currentValue = select.value;
        var options = select.querySelectorAll('option');
        
        options.forEach(function(option) {
            if (!option.value) {
                option.disabled = false;
                option.style.color = '';
                return;
            }
            
            var isUsedByOther = selectedPorts.some(function(item) {
                return item.value === option.value && item.index !== i;
            });
            
            if (isUsedByOther) {
                option.disabled = true;
                option.style.color = '#999';
            } else {
                option.disabled = false;
                option.style.color = '';
            }
        });
    }
}

// Fungsi untuk update ports data dari fields ke window variable di modal Add ODP
function updateAddOdpPortsDataFromFields() {
    var usedPorts = parseInt(document.getElementById('odpUsedPorts').value) || 0;
    var portsData = [];
    
    for (var i = 0; i < usedPorts; i++) {
        var portSelect = document.getElementById('addOdpPortSelect_' + i);
        var labelInput = document.getElementById('addOdpPortLabel_' + i);
        
        if (portSelect && labelInput) {
            portsData.push({
                port: portSelect.value,
                label: labelInput.value
            });
        }
    }
    
    window.addOdpPortsData = portsData;
}

// Fungsi untuk mendapatkan ports data sebagai JSON string di modal Add ODP
function getAddOdpPortsDataJson() {
    updateAddOdpPortsDataFromFields();
    return JSON.stringify(window.addOdpPortsData || []);
}

// Fungsi untuk reset multi-port di modal Add ODP
function resetAddOdpPortFields() {
    window.addOdpPortsData = [];
    var container = document.getElementById('addOdpPortsContainer');
    if (container) {
        container.innerHTML = '<p class="text-muted" style="text-align: center; padding: 10px;"><i class="fa fa-info-circle"></i> ' + LANG.js_no_ports_used + '</p>';
    }
}

// ==================== END FUNGSI MULTI-PORT ADD ODP ====================

// Fungsi untuk simpan ODP
function simpanOdp() {
    var parentType = document.getElementById('odpParentType').value;
    var isNewBox = document.getElementById('odpIsNewBox').value;
    
    // Validasi parent connection
    if (parentType === 'odc' && !document.getElementById('odpParentOdc').value) {
        Swal.fire({
            icon: 'warning',
            title: LANG.alert_data_incomplete,
            text: LANG.alert_select_odc_parent,
            confirmButtonColor: '#f59e0b'
        });
        return;
    }
    
    if (parentType === 'odp' && !document.getElementById('odpParentOdp').value) {
        Swal.fire({
            icon: 'warning',
            title: LANG.alert_data_incomplete,
            text: LANG.alert_select_odp_parent,
            confirmButtonColor: '#f59e0b'
        });
        return;
    }
    
    // Validasi box existing
    if (isNewBox === 'no' && !document.getElementById('odpSelectBox').value) {
        Swal.fire({
            icon: 'warning',
            title: LANG.alert_data_incomplete,
            text: LANG.alert_select_box_odp,
            confirmButtonColor: '#f59e0b'
        });
        return;
    }
    
    var name = document.getElementById('odpName').value;
    
    if (!name) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_error,
            text: LANG.alert_odp_name_required,
            confirmButtonColor: '#f59e0b'
        });
        return;
    }
    
    var portsDataJson = getAddOdpPortsDataJson();
    var fotoInput = document.getElementById('addOdpFoto');
    
    // Validasi ukuran foto (max 5MB)
    if (fotoInput && fotoInput.files.length > 0) {
        var fileSize = fotoInput.files[0].size;
        var maxSize = 5 * 1024 * 1024; // 5MB
        if (fileSize > maxSize) {
            Swal.fire({
                icon: 'error',
                title: LANG.alert_validation_failed,
                text: LANG.alert_photo_max_5mb,
                confirmButtonColor: '#ef4444'
            });
            return;
        }
    }
    
    Swal.fire({
        title: LANG.alert_saving,
        text: LANG.alert_please_wait,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: function() {
            Swal.showLoading();
        }
    });
    
    // Gunakan FormData untuk upload file
    var formData = new FormData();
    formData.append('name', name);
    formData.append('coordinates', document.getElementById('odpCoordinates').value);
    formData.append('address', document.getElementById('odpAddress').value);
    formData.append('total_ports', document.getElementById('odpTotalPorts').value);
    formData.append('used_ports', document.getElementById('odpUsedPorts').value);
    formData.append('status', document.getElementById('odpStatus').value);
    formData.append('description', document.getElementById('odpDescription').value);
    formData.append('parent_type', parentType);
    formData.append('parent_odc_id', document.getElementById('odpParentOdc').value);
    formData.append('parent_odp_id', document.getElementById('odpParentOdp').value);
    formData.append('ports_data', portsDataJson);
    formData.append('box_id', document.getElementById('odpBoxId').value);
    formData.append('is_new_box', isNewBox);
    
    // Tambahkan foto jika ada (hanya untuk Box Baru)
    if (isNewBox === 'yes' && fotoInput && fotoInput.files.length > 0) {
        formData.append('foto_odp', fotoInput.files[0]);
    }
    
    fetch(baseUrl + 'plugin/network_mapping/save-odp', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.status === 'success') {
            tutupModalOdp();
            
            Swal.fire({
                icon: 'success',
                title: LANG.alert_success,
                text: data.message,
                confirmButtonColor: '#f59e0b',
                timer: 2000,
                timerProgressBar: true
            }).then(function() {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: LANG.alert_failed,
                text: data.message,
                confirmButtonColor: '#ef4444'
            });
        }
    })
    .catch(function(error) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_error,
            text: LANG.alert_error_occurred + error.message,
            confirmButtonColor: '#ef4444'
        });
    });
}

// ==================== MULTI-SLOT ODP FUNCTIONS ====================

// Variable untuk menyimpan data box ODP saat ini
var currentOdpBoxCoordinates = null;
var currentOdpBoxData = null;

// Set mode box ODP (new atau existing)
function setOdpBoxMode(mode) {
    var optionNew = document.getElementById('optionOdpNewBox');
    var optionExisting = document.getElementById('optionOdpAddSlot');
    var groupSelectBox = document.getElementById('odpGroupSelectBox');
    var sectionLokasi = document.getElementById('sectionOdpLokasi');
    var sectionFoto = document.getElementById('sectionAddOdpFoto');
    var inputIsNewBox = document.getElementById('odpIsNewBox');
    
    if (mode === 'new') {
        optionNew.classList.add('active');
        optionExisting.classList.remove('active');
        groupSelectBox.style.display = 'none';
        sectionLokasi.style.display = 'block';
        sectionFoto.style.display = 'block'; // Tampilkan section foto untuk Box Baru
        inputIsNewBox.value = 'yes';
        
        // Reset koordinat
        document.getElementById('odpCoordinates').value = window.pendingOdpCoordinates || '';
        document.getElementById('odpCoordinatesDisplay').value = window.pendingOdpCoordinates || '';
        document.getElementById('odpBoxId').value = '';
        
        // Update title
        document.getElementById('modalOdpTitle').textContent = LANG.modal_add_odp_title;
        document.getElementById('odpNameHelp').textContent = LANG.odp_name_help_new;
    } else {
        optionNew.classList.remove('active');
        optionExisting.classList.add('active');
        groupSelectBox.style.display = 'block';
        sectionLokasi.style.display = 'none';
        sectionFoto.style.display = 'none'; // Sembunyikan section foto untuk Tambah Slot
        inputIsNewBox.value = 'no';
        
        // Reset foto preview
        hapusFotoOdpAddPreview();
        
        // Populate dropdown box existing
        populateExistingOdpBoxDropdown();
        
        // Update title
        document.getElementById('modalOdpTitle').textContent = LANG.modal_add_odp_slot_title;
        document.getElementById('odpNameHelp').textContent = LANG.odp_name_help_new_slot;
    }
}

// Populate dropdown box ODP existing
function populateExistingOdpBoxDropdown() {
    var selectBox = document.getElementById('odpSelectBox');
    selectBox.innerHTML = '<option value="">' + LANG.js_select_box_odp + '</option>';
    
    // Group ODP by koordinat dari networkData
    if (networkData.odps) {
        networkData.odps.forEach(function(odp) {
            var option = document.createElement('option');
            option.value = odp.coordinates;
            option.dataset.boxId = odp.box_id;
            option.dataset.address = odp.address || '';
            var slotText = odp.total_slots > 1 ? odp.total_slots + ' ' + LANG.js_slots : '1 ' + LANG.js_slot;
            option.textContent = (odp.box_id || odp.name) + ' (' + slotText + ') - ' + (odp.address || LANG.js_no_address);
            selectBox.appendChild(option);
        });
    }
}

// Event ketika pilih box ODP existing
function onSelectExistingOdpBox() {
    var selectBox = document.getElementById('odpSelectBox');
    var selectedOption = selectBox.options[selectBox.selectedIndex];
    
    if (selectBox.value) {
        document.getElementById('odpCoordinates').value = selectBox.value;
        document.getElementById('odpCoordinatesDisplay').value = selectBox.value;
        document.getElementById('odpBoxId').value = selectedOption.dataset.boxId || '';
        document.getElementById('odpAddress').value = selectedOption.dataset.address || '';
    }
}

// Tambah slot ODP dari popup marker
function tambahSlotOdp(coordinates, address) {
    // Simpan koordinat
    window.pendingOdpCoordinates = coordinates;
    
    // Buka modal
    document.getElementById('modalOdpOverlay').classList.add('active');
    
    // Set ke mode existing
    setOdpBoxMode('existing');
    
    // Set koordinat dan alamat
    document.getElementById('odpCoordinates').value = coordinates;
    document.getElementById('odpCoordinatesDisplay').value = coordinates;
    document.getElementById('odpAddress').value = address || '';
    
    // Select box yang sesuai di dropdown
    var selectBox = document.getElementById('odpSelectBox');
    populateExistingOdpBoxDropdown();
    
    // Cari dan select option dengan koordinat yang sama
    for (var i = 0; i < selectBox.options.length; i++) {
        if (selectBox.options[i].value === coordinates) {
            selectBox.selectedIndex = i;
            onSelectExistingOdpBox();
            break;
        }
    }
    
    // Reset form fields lainnya
    document.getElementById('odpName').value = '';
    document.getElementById('odpTotalPorts').value = '8';
    document.getElementById('odpUsedPorts').value = '0';
    document.getElementById('odpParentType').value = '';
    toggleOdpParentSelect();
    updateOdpPortTersediaAdd();
    resetAddOdpPortFields();
}

// Tampilkan detail ODP Box (multi-slot view)
function tampilkanDetailOdpBox(coordinates) {
    currentOdpBoxCoordinates = coordinates;
    
    // Fetch data dari server
    Swal.fire({
        title: LANG.alert_loading,
        text: LANG.alert_loading_odp_data,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: function() { Swal.showLoading(); }
    });
    
    fetch(baseUrl + 'plugin/network_mapping/get-odp-by-coordinates', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ coordinates: coordinates })
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        Swal.close();
        
        if (data.status === 'success') {
            currentOdpBoxData = data.data;
            renderOdpBoxDetail(data.data);
            document.getElementById('modalDetailOdpBoxOverlay').classList.add('active');
        } else {
            Swal.fire({ icon: 'error', title: LANG.alert_error, text: data.message });
        }
    })
    .catch(function(error) {
        Swal.close();
        Swal.fire({ icon: 'error', title: LANG.alert_error, text: LANG.alert_failed_fetch_data + error.message });
    });
}

// Render detail ODP Box
function renderOdpBoxDetail(boxData) {
    var slots = boxData.slots || [];
    
    // Hitung total
    var totalPorts = 0;
    var usedPorts = 0;
    slots.forEach(function(slot) {
        totalPorts += parseInt(slot.total_ports) || 0;
        usedPorts += parseInt(slot.used_ports) || 0;
    });
    var availablePorts = totalPorts - usedPorts;
    
    // Update info box
    document.getElementById('boxOdpAddress').textContent = slots[0]?.address || '-';
    document.getElementById('boxOdpCoordinates').textContent = boxData.coordinates;
    document.getElementById('boxOdpTotalSlots').textContent = slots.length;
    document.getElementById('boxOdpTotalPorts').textContent = totalPorts;
    document.getElementById('boxOdpUsedPorts').textContent = usedPorts;
    document.getElementById('boxOdpAvailablePorts').textContent = availablePorts;
    
    // Render slots accordion
    var container = document.getElementById('boxOdpSlotsContainer');
    container.innerHTML = '';
    
    slots.forEach(function(slot, index) {
        var slotAvailable = parseInt(slot.total_ports) - parseInt(slot.used_ports);
        var portsData = slot.ports_data || [];
        
        // Build parent info
        var parentInfo = '';
        if (slot.parent_type === 'odc' && slot.parent_odc_name) {
            parentInfo = '<div class="slot-parent-info slot-parent-info-odp"><i class="fa fa-plug"></i> ' + LANG.js_connected_to_odc + '<strong>' + slot.parent_odc_name + '</strong></div>';
        } else if (slot.parent_type === 'odp' && slot.parent_odp_name) {
            parentInfo = '<div class="slot-parent-info slot-parent-info-odp"><i class="fa fa-plug"></i> ' + LANG.js_connected_to_odp + '<strong>' + slot.parent_odp_name + '</strong></div>';
        }
        
        // Build ports list
        var portsHtml = '<div class="slot-ports-list">';
        
        for (var p = 1; p <= parseInt(slot.total_ports); p++) {
            // Cari apakah port ini ada di ports_data
            var portData = null;
            
            if (Array.isArray(portsData) && portsData.length > 0) {
                for (var i = 0; i < portsData.length; i++) {
                    var pd = portsData[i];
                    if (pd) {
                        var portNum = String(pd.port).replace(/[^0-9]/g, '');
                        if (parseInt(portNum) === p) {
                            portData = pd;
                            break;
                        }
                    }
                }
            }
            
            var isUsed = (portData !== null);
            var portLabel = isUsed ? (portData.label || LANG.js_port_in_use) : LANG.js_port_available_single;
            
            portsHtml += '<div class="slot-port-item slot-port-item-odp ' + (isUsed ? 'used' : '') + '">';
            portsHtml += '<span class="slot-port-number slot-port-number-odp">' + p + '</span>';
            portsHtml += '<span class="slot-port-label ' + (isUsed ? '' : 'empty') + '">' + portLabel + '</span>';
            portsHtml += '</div>';
        }
        portsHtml += '</div>';
        
        // Build accordion item
        var accordionHtml = 
            '<div class="slot-accordion-item slot-accordion-item-odp" data-slot-id="' + slot.id + '">' +
                '<div class="slot-accordion-header slot-accordion-header-odp" onclick="toggleOdpSlotAccordion(this)">' +
                    '<span class="slot-number-badge slot-number-badge-odp">' + (slot.slot_number || (index + 1)) + '</span>' +
                    '<div class="slot-info">' +
                        '<div class="slot-name">' + slot.name + '</div>' +
                        '<div class="slot-meta">' +
                            'Port: <span class="port-used">' + slot.used_ports + ' ' + LANG.js_port_used + '</span> / ' +
                            '<span class="port-available">' + slotAvailable + ' ' + LANG.js_port_available + '</span> ' + LANG.js_from + ' ' + slot.total_ports +
                        '</div>' +
                    '</div>' +
                    '<div class="slot-accordion-actions">' +
                        '<button type="button" class="btn-slot-action btn-slot-edit" onclick="event.stopPropagation(); editSlotOdp(' + slot.id + ')" title="' + LANG.btn_edit_slot + '">' +
                            '<i class="fa fa-pencil"></i>' +
                        '</button>' +
                        '<button type="button" class="btn-slot-action btn-slot-delete" onclick="event.stopPropagation(); hapusSlotOdp(' + slot.id + ', \'' + slot.name.replace(/'/g, "\\'") + '\')" title="' + LANG.btn_delete_slot + '">' +
                            '<i class="fa fa-trash"></i>' +
                        '</button>' +
                    '</div>' +
                    '<div class="slot-accordion-toggle"><i class="fa fa-chevron-down"></i></div>' +
                '</div>' +
                '<div class="slot-accordion-body">' +
                    parentInfo +
                    portsHtml +
                '</div>' +
            '</div>';
        
        container.innerHTML += accordionHtml;
    });
}

// Toggle slot accordion ODP
function toggleOdpSlotAccordion(header) {
    var body = header.nextElementSibling;
    var isActive = header.classList.contains('active');
    
    // Close all first
    document.querySelectorAll('#boxOdpSlotsContainer .slot-accordion-header').forEach(function(h) {
        h.classList.remove('active');
    });
    document.querySelectorAll('#boxOdpSlotsContainer .slot-accordion-body').forEach(function(b) {
        b.classList.remove('active');
    });
    
    // Toggle current
    if (!isActive) {
        header.classList.add('active');
        body.classList.add('active');
    }
}

// Edit single slot ODP
function editSlotOdp(slotId) {
    // Tutup modal box
    document.getElementById('modalDetailOdpBoxOverlay').classList.remove('active');
    
    // Cari data slot dari currentOdpBoxData
    var slot = null;
    if (currentOdpBoxData && currentOdpBoxData.slots) {
        slot = currentOdpBoxData.slots.find(function(s) { return s.id == slotId; });
    }
    
    if (!slot) {
        // Fallback ke networkData.odps_all
        if (networkData.odps_all) {
            slot = networkData.odps_all.find(function(o) { return o.id == slotId; });
        }
    }
    
    if (slot) {
        // Isi form edit
        document.getElementById('detailOdpId').value = slot.id;
        document.getElementById('detailOdpName').value = slot.name || '';
        document.getElementById('detailOdpCoordinates').value = slot.coordinates || '';
        document.getElementById('detailOdpCoordinatesHidden').value = slot.coordinates || '';
        document.getElementById('detailOdpAddress').value = slot.address || '';
        document.getElementById('detailOdpDescription').value = slot.description || '';
        document.getElementById('detailOdpStatus').value = slot.status || 'Active';
        document.getElementById('detailOdpTotalPorts').value = slot.total_ports || 8;
        document.getElementById('detailOdpUsedPorts').value = slot.used_ports || 0;
        document.getElementById('detailOdpSlotNumber').value = slot.slot_number || 1;
        
        // Update slot badge
        document.getElementById('detailOdpSlotBadge').textContent = slot.slot_number || 1;
        
        // Parent connection
        document.getElementById('detailOdpParentType').value = slot.parent_type || '';
        toggleDetailOdpParentSelect();
        
        if (slot.parent_type === 'odc') {
            document.getElementById('detailOdpParentOdc').value = slot.parent_odc_id || '';
        } else if (slot.parent_type === 'odp') {
            document.getElementById('detailOdpParentOdpSelect').value = slot.parent_odp_id || '';
        }
        
        // Port tersedia
        updateDetailOdpPortTersedia();
        
        // Generate port fields
        window.currentOdpPortsData = slot.ports_data || [];
        generateDetailOdpPortFields(parseInt(slot.used_ports) || 0, parseInt(slot.total_ports) || 8);
        
        // Hitung isFirstSlot DULU sebelum digunakan
        var slotNumber = parseInt(slot.slot_number) || 1;
        var isFirstSlot = true;
        var minSlotNumber = slotNumber;
        
        if (currentOdpBoxData && currentOdpBoxData.slots && currentOdpBoxData.slots.length > 0) {
            minSlotNumber = Math.min.apply(Math, currentOdpBoxData.slots.map(function(s) {
                return parseInt(s.slot_number) || 1;
            }));
            isFirstSlot = (slotNumber === minSlotNumber);
        }
        
        // Show/hide lokasi section (hanya untuk slot dengan slot_number paling kecil)
        var sectionLokasi = document.getElementById('sectionDetailOdpLokasi');
        if (isFirstSlot) {
            sectionLokasi.style.display = 'block';
        } else {
            sectionLokasi.style.display = 'none';
        }
        
        // Section Foto - hanya tampil untuk slot dengan slot_number paling kecil di box
        var sectionFoto = document.getElementById('sectionDetailOdpFoto');
        
        if (isFirstSlot) {
            sectionFoto.style.display = 'block';
            
            // Reset foto state
            document.getElementById('editOdpHapusFoto').value = '0';
            document.getElementById('editOdpFoto').value = '';
            
            // Tampilkan foto existing jika ada
            var fotoPlaceholder = document.getElementById('editOdpFotoPlaceholder');
            var fotoPreview = document.getElementById('editOdpFotoPreview');
            var fotoPreviewImg = document.getElementById('editOdpFotoPreviewImg');
            
            // Cari foto dari slot pertama (slot dengan slot_number paling kecil)
            var fotoSlot = null;
            if (currentOdpBoxData && currentOdpBoxData.slots) {
                fotoSlot = currentOdpBoxData.slots.find(function(s) {
                    return parseInt(s.slot_number) === minSlotNumber;
                });
            }
            
            if (fotoSlot && fotoSlot.foto && fotoSlot.foto_url) {
                fotoPreviewImg.src = fotoSlot.foto_url;
                fotoPlaceholder.style.display = 'none';
                fotoPreview.style.display = 'block';
            } else if (slot.foto && slot.foto_url) {
                fotoPreviewImg.src = slot.foto_url;
                fotoPlaceholder.style.display = 'none';
                fotoPreview.style.display = 'block';
            } else {
                fotoPlaceholder.style.display = 'flex';
                fotoPreview.style.display = 'none';
                fotoPreviewImg.src = '';
            }
        } else {
            // Sembunyikan section foto untuk slot selain slot pertama
            sectionFoto.style.display = 'none';
        }
        
        // Simpan data untuk referensi
        window.currentDetailOdpId = slot.id;
        window.currentDetailOdpData = slot;
        
        // Buka modal edit slot
        document.getElementById('modalDetailOdpOverlay').classList.add('active');
    } else {
        Swal.fire({ icon: 'error', title: LANG.alert_error, text: LANG.alert_slot_data_not_found });
    }
}

// Hapus single slot ODP
function hapusSlotOdp(slotId, slotName) {
    Swal.fire({
        title: LANG.alert_delete_odp_slot_title,
        html: LANG.alert_delete_odp_slot_text_prefix + '<strong>' + slotName + '</strong>' + LANG.alert_delete_odp_slot_text_suffix,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: LANG.alert_yes_delete,
        cancelButtonText: LANG.alert_cancel,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280'
    }).then(function(result) {
        if (result.isConfirmed) {
            Swal.fire({
                title: LANG.alert_deleting,
                text: LANG.alert_please_wait,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: function() { Swal.showLoading(); }
            });
            
            fetch(baseUrl + 'plugin/network_mapping/delete-odp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ odp_id: slotId })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: LANG.alert_success,
                        text: data.message,
                        confirmButtonColor: '#f59e0b'
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: LANG.alert_failed, text: data.message });
                }
            })
            .catch(function(error) {
                Swal.fire({ icon: 'error', title: LANG.alert_error, text: error.message });
            });
        }
    });
}

// Tambah slot ODP dari modal detail box
function tambahSlotOdpDariDetail() {
    if (currentOdpBoxCoordinates) {
        // Tutup modal detail
        document.getElementById('modalDetailOdpBoxOverlay').classList.remove('active');
        
        // Buka modal tambah dengan mode existing
        var address = currentOdpBoxData?.slots?.[0]?.address || '';
        tambahSlotOdp(currentOdpBoxCoordinates, address);
    }
}

// Tutup modal detail ODP Box
function tutupModalDetailOdpBox() {
    document.getElementById('modalDetailOdpBoxOverlay').classList.remove('active');
    currentOdpBoxCoordinates = null;
    currentOdpBoxData = null;
}

// Override fungsi bukaModalOdp untuk support multi-slot
var originalBukaModalOdp = typeof bukaModalOdp === 'function' ? bukaModalOdp : null;
function bukaModalOdp(coordinates) {
    // Simpan koordinat pending
    window.pendingOdpCoordinates = coordinates;
    
    // Reset ke mode new box
    setOdpBoxMode('new');
    
    // Set koordinat
    document.getElementById('odpCoordinates').value = coordinates;
    document.getElementById('odpCoordinatesDisplay').value = coordinates;
    
    // Reset form
    document.getElementById('formOdp').reset();
    document.getElementById('odpCoordinates').value = coordinates;
    document.getElementById('odpCoordinatesDisplay').value = coordinates;
    document.getElementById('odpTotalPorts').value = '8';
    document.getElementById('odpUsedPorts').value = '0';
    document.getElementById('odpIsNewBox').value = 'yes';
    
    updateOdpPortTersediaAdd();
    resetAddOdpPortFields();
    
    // Buka modal
    document.getElementById('modalOdpOverlay').classList.add('active');
}

// ==================== FUNGSI DETAIL/EDIT ODP ====================

// Fungsi untuk menampilkan modal detail ODP
function tampilkanDetailOdp(id) {
    var odp = null;
    if (networkData.odps) {
        odp = networkData.odps.find(function(o) {
            return o.id == id;
        });
    }
    
    if (odp) {
        document.getElementById('detailOdpId').value = id;
        window.currentDetailOdpId = id;
        window.currentDetailOdpData = odp;
        
        // Isi data ke modal
        document.getElementById('detailOdpName').value = odp.name || '';
        document.getElementById('detailOdpAddress').value = odp.address || '';
        document.getElementById('detailOdpDescription').value = odp.description || '';
        
        // Status
        var statusSelect = document.getElementById('detailOdpStatus');
        statusSelect.value = odp.status || 'Active';
        
        // Coordinates
        document.getElementById('detailOdpCoordinates').value = odp.coordinates || '';
        document.getElementById('detailOdpCoordinatesHidden').value = odp.coordinates || '';
        
        // Parent Connection
        var parentTypeSelect = document.getElementById('detailOdpParentType');
        var detailOdpGroupParentOdc = document.getElementById('detailOdpGroupParentOdc');
        var detailOdpGroupParentOdp = document.getElementById('detailOdpGroupParentOdp');
        
        parentTypeSelect.value = odp.parent_type || '';
        
        window.currentOdpParentType = odp.parent_type || '';
        window.currentOdpParentOdcId = odp.parent_odc_id || '';
        window.currentOdpParentOdpId = odp.parent_odp_id || '';
        
        detailOdpGroupParentOdc.style.display = 'none';
        detailOdpGroupParentOdp.style.display = 'none';
        
        if (odp.parent_type === 'odc') {
            detailOdpGroupParentOdc.style.display = 'block';
            document.getElementById('detailOdpParentOdc').value = odp.parent_odc_id || '';
        } else if (odp.parent_type === 'odp') {
            detailOdpGroupParentOdp.style.display = 'block';
            document.getElementById('detailOdpParentOdpSelect').value = odp.parent_odp_id || '';
        }
        
        // Port Configuration
        document.getElementById('detailOdpTotalPorts').value = odp.total_ports || 8;
        document.getElementById('detailOdpUsedPorts').value = odp.used_ports || 0;
        
        updateDetailOdpPortTersedia();
        
        window.currentOdpPortsData = odp.ports_data || [];
        
        generateDetailOdpPortFields(parseInt(odp.used_ports) || 0, parseInt(odp.total_ports) || 8);
        
        document.getElementById('modalDetailOdpOverlay').classList.add('active');
    } else {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_odp_not_found,
            text: LANG.alert_odp_not_found_text
        });
    }
}

// Toggle parent select di modal detail ODP
function toggleDetailOdpParentSelect() {
    var parentType = document.getElementById('detailOdpParentType').value;
    var groupOdc = document.getElementById('detailOdpGroupParentOdc');
    var groupOdp = document.getElementById('detailOdpGroupParentOdp');
    
    groupOdc.style.display = 'none';
    groupOdp.style.display = 'none';
    
    if (parentType === 'odc') {
        groupOdc.style.display = 'block';
        if (window.currentOdpParentType !== 'odc') {
            document.getElementById('detailOdpParentOdc').value = '';
        }
    } else if (parentType === 'odp') {
        groupOdp.style.display = 'block';
        if (window.currentOdpParentType !== 'odp') {
            document.getElementById('detailOdpParentOdpSelect').value = '';
        }
    }
}

// ==================== FUNGSI MULTI-PORT DETAIL ODP ====================

// Fungsi untuk ubah port di modal detail ODP
function ubahDetailOdpPort(tipe, delta) {
    var inputId = tipe === 'total' ? 'detailOdpTotalPorts' : 'detailOdpUsedPorts';
    var input = document.getElementById(inputId);
    var nilai = parseInt(input.value) || 0;
    
    var totalPorts = parseInt(document.getElementById('detailOdpTotalPorts').value) || 8;
    var usedPorts = parseInt(document.getElementById('detailOdpUsedPorts').value) || 0;
    
    if (tipe === 'total') {
        nilai += delta;
        if (nilai < 1) nilai = 1;
        if (nilai > 128) nilai = 128;
        if (nilai < usedPorts) nilai = usedPorts;
        input.value = nilai;
        
        updateOdpPortsDataFromFields();
        generateDetailOdpPortFields(usedPorts, nilai);
    } else {
        nilai += delta;
        if (nilai < 0) nilai = 0;
        if (nilai > totalPorts) nilai = totalPorts;
        input.value = nilai;
        
        generateDetailOdpPortFields(nilai, totalPorts);
    }
    
    updateDetailOdpPortTersedia();
}

// Fungsi untuk update tampilan port tersedia di modal detail ODP
function updateDetailOdpPortTersedia() {
    var totalPorts = parseInt(document.getElementById('detailOdpTotalPorts').value) || 0;
    var usedPorts = parseInt(document.getElementById('detailOdpUsedPorts').value) || 0;
    var tersedia = totalPorts - usedPorts;
    
    var portTersediaElement = document.getElementById('detailOdpPortTersedia');
    if (portTersediaElement) {
        portTersediaElement.textContent = tersedia;
        
        var portInfoSimple = portTersediaElement.closest('.port-info-simple');
        if (portInfoSimple) {
            if (tersedia === 0) {
                portInfoSimple.style.background = '#fee2e2';
                portInfoSimple.style.borderColor = '#fca5a5';
                portInfoSimple.style.color = '#7f1d1d';
                portInfoSimple.querySelector('strong').style.color = '#991b1b';
            } else if (tersedia <= totalPorts * 0.2) {
                portInfoSimple.style.background = '#fef3c7';
                portInfoSimple.style.borderColor = '#fde047';
                portInfoSimple.style.color = '#713f12';
                portInfoSimple.querySelector('strong').style.color = '#854d0e';
            } else {
                portInfoSimple.style.background = '#fefce8';
                portInfoSimple.style.borderColor = '#fde047';
                portInfoSimple.style.color = '#713f12';
                portInfoSimple.querySelector('strong').style.color = '#ca8a04';
            }
        }
    }
}

// Fungsi untuk generate multi-port fields di modal detail ODP
function generateDetailOdpPortFields(usedPorts, totalPorts) {
    var container = document.getElementById('detailOdpPortsContainer');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (usedPorts <= 0) {
        container.innerHTML = '<p class="text-muted" style="text-align: center; padding: 10px;"><i class="fa fa-info-circle"></i> ' + LANG.js_no_ports_used + '</p>';
        return;
    }
    
    var headerHtml = '<div class="ports-fields-header" style="background: #fefce8; padding: 10px; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid #f59e0b;">' +
        '<strong style="color: #92400e;"><i class="fa fa-plug"></i> ' + LANG.js_detail + ' ' + usedPorts + ' ' + LANG.js_ports_used + '</strong>' +
        '</div>';
    container.innerHTML = headerHtml;
    
    for (var i = 0; i < usedPorts; i++) {
        var portIndex = i + 1;
        
        var existingPort = '';
        var existingLabel = '';
        if (window.currentOdpPortsData && window.currentOdpPortsData[i]) {
            existingPort = window.currentOdpPortsData[i].port || '';
            existingLabel = window.currentOdpPortsData[i].label || '';
        }
        
        var fieldHtml = '<div class="port-field-item" style="background: #fafafa; padding: 12px; border-radius: 6px; margin-bottom: 10px; border: 1px solid #e5e7eb;">' +
            '<div style="font-weight: 600; margin-bottom: 8px; color: #4b5563;"><i class="fa fa-circle" style="font-size: 8px; color: #f59e0b;"></i> ' + LANG.js_port_number + portIndex + '</div>' +
            '<div class="row">' +
                '<div class="col-md-6">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.js_port_number_label + '</label>' +
                        '<select class="form-control input-sm" id="detailOdpPortSelect_' + i + '" onchange="onDetailOdpPortSelectChange(' + i + ')">' +
                            generateDetailOdpPortOptionsHtml(totalPorts, existingPort) +
                        '</select>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-6">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.js_port_label_label + '</label>' +
                        '<input type="text" class="form-control input-sm" id="detailOdpPortLabel_' + i + '" value="' + escapeHtml(existingLabel) + '" placeholder="' + LANG.js_port_label_placeholder_odp + '" onchange="updateOdpPortsDataFromFields()">' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        container.innerHTML += fieldHtml;
    }
    
    updateAllDetailOdpPortDropdownsDisabledState();
}

// Fungsi untuk generate options Port di modal detail ODP
function generateDetailOdpPortOptionsHtml(totalPorts, selectedValue) {
    var html = '<option value="">' + LANG.js_select_port + '</option>';
    for (var i = 1; i <= totalPorts; i++) {
        var portValue = 'Port ' + i;
        var selected = (portValue === selectedValue) ? 'selected' : '';
        html += '<option value="' + portValue + '" ' + selected + '>' + portValue + '</option>';
    }
    return html;
}

// Fungsi yang dipanggil ketika Port dropdown berubah di modal detail ODP
function onDetailOdpPortSelectChange(changedIndex) {
    updateOdpPortsDataFromFields();
    updateAllDetailOdpPortDropdownsDisabledState();
}

// Fungsi untuk update disabled state semua dropdown Port di modal detail ODP
function updateAllDetailOdpPortDropdownsDisabledState() {
    var usedPorts = parseInt(document.getElementById('detailOdpUsedPorts').value) || 0;
    
    var selectedPorts = [];
    for (var i = 0; i < usedPorts; i++) {
        var select = document.getElementById('detailOdpPortSelect_' + i);
        if (select && select.value) {
            selectedPorts.push({
                index: i,
                value: select.value
            });
        }
    }
    
    for (var i = 0; i < usedPorts; i++) {
        var select = document.getElementById('detailOdpPortSelect_' + i);
        if (!select) continue;
        
        var currentValue = select.value;
        var options = select.querySelectorAll('option');
        
        options.forEach(function(option) {
            if (!option.value) {
                option.disabled = false;
                option.style.color = '';
                return;
            }
            
            var isUsedByOther = selectedPorts.some(function(item) {
                return item.value === option.value && item.index !== i;
            });
            
            if (isUsedByOther) {
                option.disabled = true;
                option.style.color = '#999';
            } else {
                option.disabled = false;
                option.style.color = '';
            }
        });
    }
}

// Fungsi untuk update ports data dari fields ke window variable di modal detail ODP
function updateOdpPortsDataFromFields() {
    var usedPorts = parseInt(document.getElementById('detailOdpUsedPorts').value) || 0;
    var portsData = [];
    
    for (var i = 0; i < usedPorts; i++) {
        var portSelect = document.getElementById('detailOdpPortSelect_' + i);
        var labelInput = document.getElementById('detailOdpPortLabel_' + i);
        
        if (portSelect && labelInput) {
            portsData.push({
                port: portSelect.value,
                label: labelInput.value
            });
        }
    }
    
    window.currentOdpPortsData = portsData;
}

// Fungsi untuk mendapatkan ports data sebagai JSON string di modal detail ODP
function getOdpPortsDataJson() {
    updateOdpPortsDataFromFields();
    return JSON.stringify(window.currentOdpPortsData || []);
}

// ==================== END FUNGSI MULTI-PORT DETAIL ODP ====================

// Fungsi untuk menutup modal detail ODP
function tutupModalDetailOdp() {
    document.getElementById('modalDetailOdpOverlay').classList.remove('active');
    window.currentDetailOdpId = null;
    window.currentDetailOdpData = null;
    
    window.remapOdpId = null;
    window.remapOdpData = null;
    
    if (isPickMode && pickModeType === 'remap-odp') {
        batalkanModePick();
    }
    
    if (tempMarker) {
        map.removeLayer(tempMarker);
        tempMarker = null;
    }
}

// Fungsi untuk mengaktifkan mode remap koordinat ODP
function aktifkanModeRemapOdp() {
    window.remapOdpId = document.getElementById('detailOdpId').value;
    
    updateOdpPortsDataFromFields();
    
    window.remapOdpData = {
        name: document.getElementById('detailOdpName').value,
        status: document.getElementById('detailOdpStatus').value,
        address: document.getElementById('detailOdpAddress').value,
        description: document.getElementById('detailOdpDescription').value,
        parent_type: document.getElementById('detailOdpParentType').value,
        parent_odc_id: document.getElementById('detailOdpParentOdc').value,
        parent_odp_id: document.getElementById('detailOdpParentOdpSelect').value,
        total_ports: document.getElementById('detailOdpTotalPorts').value,
        used_ports: document.getElementById('detailOdpUsedPorts').value,
        ports_data: window.currentOdpPortsData || []
    };
    
    document.getElementById('modalDetailOdpOverlay').classList.remove('active');
    
    isPickMode = true;
    pickModeType = 'remap-odp';
    
    var pickIndicator = document.getElementById('pickModeIndicator');
    var pickText = pickIndicator.querySelector('span');
    pickText.textContent = 'Klik pada peta untuk mengubah lokasi ODP';
    pickIndicator.classList.add('active');
    
    document.getElementById('map').style.cursor = 'crosshair';
    
    setTimeout(function() {
        map.on('click', onMapClickPick);
    }, 300);
}

// Fungsi untuk buka kembali modal detail ODP setelah pilih lokasi baru
function bukaKembaliModalDetailOdp(newCoordinates) {
    var odp = null;
    if (networkData.odps && window.remapOdpId) {
        odp = networkData.odps.find(function(o) {
            return o.id == window.remapOdpId;
        });
    }
    
    if (odp && window.remapOdpData) {
        document.getElementById('detailOdpId').value = window.remapOdpId;
        document.getElementById('detailOdpName').value = window.remapOdpData.name;
        document.getElementById('detailOdpStatus').value = window.remapOdpData.status;
        document.getElementById('detailOdpAddress').value = window.remapOdpData.address;
        document.getElementById('detailOdpDescription').value = window.remapOdpData.description;
        
        document.getElementById('detailOdpCoordinates').value = newCoordinates;
        document.getElementById('detailOdpCoordinatesHidden').value = newCoordinates;
        
        // Restore parent connection
        var parentTypeSelect = document.getElementById('detailOdpParentType');
        var detailOdpGroupParentOdc = document.getElementById('detailOdpGroupParentOdc');
        var detailOdpGroupParentOdp = document.getElementById('detailOdpGroupParentOdp');
        
        var savedParentType = window.remapOdpData.parent_type || odp.parent_type || '';
        parentTypeSelect.value = savedParentType;
        
        detailOdpGroupParentOdc.style.display = 'none';
        detailOdpGroupParentOdp.style.display = 'none';
        
        if (savedParentType === 'odc') {
            detailOdpGroupParentOdc.style.display = 'block';
            document.getElementById('detailOdpParentOdc').value = window.remapOdpData.parent_odc_id || odp.parent_odc_id || '';
        } else if (savedParentType === 'odp') {
            detailOdpGroupParentOdp.style.display = 'block';
            document.getElementById('detailOdpParentOdpSelect').value = window.remapOdpData.parent_odp_id || odp.parent_odp_id || '';
        }
        
        // Restore port configuration
        var savedTotalPorts = window.remapOdpData.total_ports || odp.total_ports || 8;
        var savedUsedPorts = window.remapOdpData.used_ports || odp.used_ports || 0;
        
        document.getElementById('detailOdpTotalPorts').value = savedTotalPorts;
        document.getElementById('detailOdpUsedPorts').value = savedUsedPorts;
        
        updateDetailOdpPortTersedia();
        
        window.currentOdpPortsData = window.remapOdpData.ports_data || odp.ports_data || [];
        
        generateDetailOdpPortFields(parseInt(savedUsedPorts), parseInt(savedTotalPorts));
        
        window.currentDetailOdpId = window.remapOdpId;
        window.currentDetailOdpData = odp;
        
        document.getElementById('modalDetailOdpOverlay').classList.add('active');
        
        Swal.fire({
            icon: 'success',
            title: LANG.alert_location_changed,
            text: LANG.alert_location_changed_text,
            timer: 2000,
            showConfirmButton: false,
            customClass: {
                container: 'swal-high-zindex'
            }
        });
        
        window.remapOdpId = null;
        window.remapOdpData = null;
    }
}

// Fungsi untuk simpan edit ODP
function simpanEditOdp() {
    var odp_id = document.getElementById('detailOdpId').value;
    var name = document.getElementById('detailOdpName').value.trim();
    var status = document.getElementById('detailOdpStatus').value;
    var coordinates = document.getElementById('detailOdpCoordinatesHidden').value;
    var address = document.getElementById('detailOdpAddress').value.trim();
    var description = document.getElementById('detailOdpDescription').value.trim();
    
    var parent_type = document.getElementById('detailOdpParentType').value;
    var parent_odc_id = '';
    var parent_odp_id = '';
    
    if (parent_type === 'odc') {
        parent_odc_id = document.getElementById('detailOdpParentOdc').value;
    } else if (parent_type === 'odp') {
        parent_odp_id = document.getElementById('detailOdpParentOdpSelect').value;
    }
    
    var total_ports = document.getElementById('detailOdpTotalPorts').value;
    var used_ports = document.getElementById('detailOdpUsedPorts').value;
    var ports_data = getOdpPortsDataJson();
    
    var hapusFoto = document.getElementById('editOdpHapusFoto').value;
    var fotoInput = document.getElementById('editOdpFoto');
    
    // Validasi
    if (!name) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_validation_failed,
            text: LANG.alert_odp_name_required
        });
        return;
    }
    
    if (!coordinates) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_validation_failed,
            text: LANG.alert_coordinates_required
        });
        return;
    }
    
    if (parent_type === 'odc' && !parent_odc_id) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_validation_failed,
            text: LANG.alert_select_odc_parent
        });
        return;
    }
    
    if (parent_type === 'odp' && !parent_odp_id) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_validation_failed,
            text: LANG.alert_select_odp_parent
        });
        return;
    }
    
    // Validasi ukuran foto
    if (fotoInput && fotoInput.files.length > 0) {
        if (fotoInput.files[0].size > 5 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: LANG.alert_validation_failed,
                text: LANG.alert_photo_max_5mb
            });
            return;
        }
    }
    
    Swal.fire({
        title: LANG.alert_save_changes_odp_title,
        text: LANG.alert_save_changes_odp_text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-save"></i> ' + LANG.alert_yes_save,
        cancelButtonText: LANG.alert_cancel,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: LANG.alert_saving,
                text: LANG.alert_please_wait,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Gunakan FormData untuk upload file
            var formData = new FormData();
            formData.append('odp_id', odp_id);
            formData.append('name', name);
            formData.append('status', status);
            formData.append('coordinates', coordinates);
            formData.append('address', address);
            formData.append('description', description);
            formData.append('parent_type', parent_type);
            formData.append('parent_odc_id', parent_odc_id);
            formData.append('parent_odp_id', parent_odp_id);
            formData.append('total_ports', total_ports);
            formData.append('used_ports', used_ports);
            formData.append('ports_data', ports_data);
            formData.append('hapus_foto', hapusFoto);
            
            // Tambahkan foto jika ada
            if (fotoInput && fotoInput.files.length > 0) {
                formData.append('foto_odp', fotoInput.files[0]);
            }
            
            fetch(baseUrl + 'plugin/network_mapping/update-odp', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: LANG.alert_success,
                        text: LANG.alert_odp_changes_saved,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: LANG.alert_failed,
                        text: data.message || LANG.alert_save_error
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: LANG.alert_error,
                    text: LANG.alert_server_connection_failed
                });
            });
        }
    });
}

// Fungsi untuk hapus ODP
function hapusOdp() {
    var odp_id = document.getElementById('detailOdpId').value;
    var odp_name = document.getElementById('detailOdpName').value;
    
    if (!odp_id) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_error,
            text: LANG.alert_odp_id_not_found
        });
        return;
    }
    
    Swal.fire({
        title: LANG.alert_delete_odp_title,
        text: LANG.alert_delete_odp_prefix + ' "' + odp_name + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-trash"></i> ' + LANG.alert_yes_delete,
        cancelButtonText: LANG.alert_cancel,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: LANG.alert_deleting,
                text: LANG.alert_please_wait,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(baseUrl + 'plugin/network_mapping/delete-odp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    odp_id: odp_id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: LANG.alert_success,
                        text: LANG.alert_odp_deleted_success,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: LANG.alert_failed,
                        text: data.message || LANG.alert_odp_delete_error
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: LANG.alert_error,
                    text: LANG.alert_server_connection_failed
                });
            });
        }
    });
}

// Fungsi untuk pusatkan peta ke ODP dari modal detail
function pusatkanPetaKeOdpDetail() {
    if (window.currentDetailOdpId) {
        var odp = networkData.odps.find(function(o) { return o.id == window.currentDetailOdpId; });
        if (odp) {
            var coords = odp.coordinates.split(',').map(parseFloat);
            map.setView(coords, 18);
            tutupModalDetailOdp();
        }
    }
}

// ==================== END FUNGSI ODP ====================

// ==================== FUNGSI TIANG ====================

// Fungsi untuk buka modal Tiang
function bukaModalTiang(coordinates) {
    document.getElementById('tiangCoordinates').value = coordinates;
    document.getElementById('tiangCoordinatesDisplay').value = coordinates;
    document.getElementById('modalTiangOverlay').classList.add('active');
}

// Fungsi untuk tutup modal Tiang
function tutupModalTiang() {
    document.getElementById('modalTiangOverlay').classList.remove('active');
    document.getElementById('formTiang').reset();
    
    // Reset foto preview - DITAMBAHKAN
    hapusFotoTiangPreview();
    
    // Hapus temp marker
    if (tempMarker) {
        map.removeLayer(tempMarker);
        tempMarker = null;
    }
}

// Fungsi untuk simpan Tiang
function simpanTiang() {
    var name = document.getElementById('tiangName').value;
    var coordinates = document.getElementById('tiangCoordinates').value;
    var fotoInput = document.getElementById('tiangFoto');
    
    if (!name) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_error,
            text: LANG.alert_tiang_name_required,
            confirmButtonColor: '#78716c'
        });
        return;
    }
    
    // Validasi ukuran foto (max 5MB)
    if (fotoInput && fotoInput.files.length > 0) {
        var fileSize = fotoInput.files[0].size;
        var maxSize = 5 * 1024 * 1024; // 5MB
        if (fileSize > maxSize) {
            Swal.fire({
                icon: 'error',
                title: LANG.alert_error,
                text: LANG.alert_photo_max_5mb,
                confirmButtonColor: '#78716c'
            });
            return;
        }
    }
    
    Swal.fire({
        title: LANG.alert_saving,
        text: LANG.alert_please_wait,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Gunakan FormData untuk upload file
    var formData = new FormData();
    formData.append('name', name);
    formData.append('coordinates', coordinates);
    formData.append('panjang', document.getElementById('tiangPanjang').value);
    formData.append('bahan', document.getElementById('tiangBahan').value);
    formData.append('slack_kabel', document.getElementById('tiangSlackKabel').value);
    formData.append('status', document.getElementById('tiangStatus').value);
    formData.append('address', document.getElementById('tiangAddress').value);
    formData.append('description', document.getElementById('tiangDescription').value);
    
    // Tambahkan foto jika ada
    if (fotoInput && fotoInput.files.length > 0) {
        formData.append('foto_tiang', fotoInput.files[0]);
    }
    
    fetch(baseUrl + 'plugin/network_mapping/save-tiang', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            tutupModalTiang();
            
            Swal.fire({
                icon: 'success',
                title: LANG.alert_success,
                text: data.message,
                confirmButtonColor: '#78716c',
                timer: 2000,
                timerProgressBar: true
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: LANG.alert_failed,
                text: data.message,
                confirmButtonColor: '#ef4444'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_error,
            text: LANG.alert_error_occurred + error.message,
            confirmButtonColor: '#ef4444'
        });
    });
}

// ==================== FUNGSI DETAIL/EDIT TIANG ====================

// Fungsi untuk menampilkan modal detail Tiang
function tampilkanDetailTiang(id) {
    var tiang = null;
    if (networkData.tiang) {
        tiang = networkData.tiang.find(function(t) {
            return t.id == id;
        });
    }
    
    if (tiang) {
        document.getElementById('detailTiangId').value = id;
        window.currentDetailTiangId = id;
        window.currentDetailTiangData = tiang;
        
        // Isi data ke modal
        document.getElementById('detailTiangName').value = tiang.name || '';
        document.getElementById('detailTiangPanjang').value = tiang.panjang || '';
        document.getElementById('detailTiangBahan').value = tiang.bahan || '';
        document.getElementById('detailTiangSlackKabel').value = tiang.slack_kabel || '';
        document.getElementById('detailTiangAddress').value = tiang.address || '';
        document.getElementById('detailTiangDescription').value = tiang.description || '';
        
        // Status
        var statusSelect = document.getElementById('detailTiangStatus');
        statusSelect.value = tiang.status || 'Aktif';
        
        // Coordinates
        document.getElementById('detailTiangCoordinates').value = tiang.coordinates || '';
        document.getElementById('detailTiangCoordinatesHidden').value = tiang.coordinates || '';
        
        // Reset foto state
        document.getElementById('editTiangHapusFoto').value = '0';
        document.getElementById('editTiangFoto').value = '';
        
        // Tampilkan foto existing jika ada
        var fotoPlaceholder = document.getElementById('editTiangFotoPlaceholder');
        var fotoPreview = document.getElementById('editTiangFotoPreview');
        var fotoPreviewImg = document.getElementById('editTiangFotoPreviewImg');
        
        if (tiang.foto && tiang.foto_url) {
            fotoPreviewImg.src = tiang.foto_url;
            fotoPlaceholder.style.display = 'none';
            fotoPreview.style.display = 'block';
        } else {
            fotoPlaceholder.style.display = 'flex';
            fotoPreview.style.display = 'none';
            fotoPreviewImg.src = '';
        }
        
        document.getElementById('modalDetailTiangOverlay').classList.add('active');
    } else {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_tiang_not_found,
            text: LANG.alert_tiang_not_found_text
        });
    }
}

// Fungsi untuk menutup modal detail Tiang
function tutupModalDetailTiang() {
    document.getElementById('modalDetailTiangOverlay').classList.remove('active');
    window.currentDetailTiangId = null;
    window.currentDetailTiangData = null;
    
    window.remapTiangId = null;
    window.remapTiangData = null;
    
    if (isPickMode && pickModeType === 'remap-tiang') {
        batalkanModePick();
    }
    
    if (tempMarker) {
        map.removeLayer(tempMarker);
        tempMarker = null;
    }
}

// Fungsi untuk mengaktifkan mode remap koordinat Tiang
function aktifkanModeRemapTiang() {
    window.remapTiangId = document.getElementById('detailTiangId').value;
    
    window.remapTiangData = {
        name: document.getElementById('detailTiangName').value,
        panjang: document.getElementById('detailTiangPanjang').value,
        bahan: document.getElementById('detailTiangBahan').value,
        slack_kabel: document.getElementById('detailTiangSlackKabel').value,
        status: document.getElementById('detailTiangStatus').value,
        address: document.getElementById('detailTiangAddress').value,
        description: document.getElementById('detailTiangDescription').value
    };
    
    document.getElementById('modalDetailTiangOverlay').classList.remove('active');
    
    isPickMode = true;
    pickModeType = 'remap-tiang';
    
    var pickIndicator = document.getElementById('pickModeIndicator');
    var pickText = pickIndicator.querySelector('span');
    pickText.textContent = 'Klik pada peta untuk mengubah lokasi Tiang';
    pickIndicator.classList.add('active');
    
    document.getElementById('map').style.cursor = 'crosshair';
    
    setTimeout(function() {
        map.on('click', onMapClickPick);
    }, 300);
}

// Fungsi untuk buka kembali modal detail Tiang setelah pilih lokasi baru
function bukaKembaliModalDetailTiang(newCoordinates) {
    var tiang = null;
    if (networkData.tiang && window.remapTiangId) {
        tiang = networkData.tiang.find(function(t) {
            return t.id == window.remapTiangId;
        });
    }
    
    if (tiang && window.remapTiangData) {
        document.getElementById('detailTiangId').value = window.remapTiangId;
        document.getElementById('detailTiangName').value = window.remapTiangData.name;
        document.getElementById('detailTiangPanjang').value = window.remapTiangData.panjang;
        document.getElementById('detailTiangBahan').value = window.remapTiangData.bahan;
        document.getElementById('detailTiangSlackKabel').value = window.remapTiangData.slack_kabel;
        document.getElementById('detailTiangStatus').value = window.remapTiangData.status;
        document.getElementById('detailTiangAddress').value = window.remapTiangData.address;
        document.getElementById('detailTiangDescription').value = window.remapTiangData.description;
        
        document.getElementById('detailTiangCoordinates').value = newCoordinates;
        document.getElementById('detailTiangCoordinatesHidden').value = newCoordinates;
        
        window.currentDetailTiangId = window.remapTiangId;
        window.currentDetailTiangData = tiang;
        
        document.getElementById('modalDetailTiangOverlay').classList.add('active');
        
        Swal.fire({
            icon: 'success',
            title: LANG.alert_location_changed,
            text: LANG.alert_location_changed_text,
            timer: 2000,
            showConfirmButton: false,
            customClass: {
                container: 'swal-high-zindex'
            }
        });
        
        window.remapTiangId = null;
        window.remapTiangData = null;
    }
}

// Fungsi untuk simpan edit Tiang
function simpanEditTiang() {
    var tiang_id = document.getElementById('detailTiangId').value;
    var name = document.getElementById('detailTiangName').value.trim();
    var panjang = document.getElementById('detailTiangPanjang').value;
    var bahan = document.getElementById('detailTiangBahan').value;
    var slack_kabel = document.getElementById('detailTiangSlackKabel').value;
    var status = document.getElementById('detailTiangStatus').value;
    var coordinates = document.getElementById('detailTiangCoordinatesHidden').value;
    var address = document.getElementById('detailTiangAddress').value.trim();
    var description = document.getElementById('detailTiangDescription').value.trim();
    var hapusFoto = document.getElementById('editTiangHapusFoto').value;
    var fotoInput = document.getElementById('editTiangFoto');
    
    // Validasi
    if (!name) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_validation_failed,
            text: LANG.alert_tiang_name_required
        });
        return;
    }
    
    if (!coordinates) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_validation_failed,
            text: LANG.alert_coordinates_required
        });
        return;
    }
    
    // Validasi ukuran foto (max 5MB)
    if (fotoInput && fotoInput.files.length > 0) {
        var fileSize = fotoInput.files[0].size;
        var maxSize = 5 * 1024 * 1024; // 5MB
        if (fileSize > maxSize) {
            Swal.fire({
                icon: 'error',
                title: LANG.alert_validation_failed,
                text: LANG.alert_photo_max_5mb
            });
            return;
        }
    }
    
    Swal.fire({
        title: LANG.alert_save_changes_tiang_title,
        text: LANG.alert_save_changes_tiang_text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-save"></i> ' + LANG.alert_yes_save,
        cancelButtonText: LANG.alert_cancel,
        confirmButtonColor: '#78716c',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: LANG.alert_saving,
                text: LANG.alert_please_wait,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Gunakan FormData untuk upload file
            var formData = new FormData();
            formData.append('tiang_id', tiang_id);
            formData.append('name', name);
            formData.append('panjang', panjang);
            formData.append('bahan', bahan);
            formData.append('slack_kabel', slack_kabel);
            formData.append('status', status);
            formData.append('coordinates', coordinates);
            formData.append('address', address);
            formData.append('description', description);
            formData.append('hapus_foto', hapusFoto);
            
            // Tambahkan foto jika ada
            if (fotoInput && fotoInput.files.length > 0) {
                formData.append('foto_tiang', fotoInput.files[0]);
            }
            
            fetch(baseUrl + 'plugin/network_mapping/update-tiang', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: LANG.alert_success,
                        text: LANG.alert_tiang_changes_saved,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: LANG.alert_failed,
                        text: data.message || LANG.alert_save_error
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: LANG.alert_error,
                    text: LANG.alert_server_connection_failed
                });
            });
        }
    });
}

// Fungsi untuk hapus Tiang
function hapusTiang() {
    var tiang_id = document.getElementById('detailTiangId').value;
    var tiang_name = document.getElementById('detailTiangName').value;
    
    if (!tiang_id) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_error,
            text: LANG.alert_tiang_id_not_found
        });
        return;
    }
    
    Swal.fire({
        title: LANG.alert_delete_tiang_title,
        text: LANG.alert_delete_tiang_prefix + ' "' + tiang_name + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-trash"></i> ' + LANG.alert_yes_delete,
        cancelButtonText: LANG.alert_cancel,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: LANG.alert_deleting,
                text: LANG.alert_please_wait,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(baseUrl + 'plugin/network_mapping/delete-tiang', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    tiang_id: tiang_id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: LANG.alert_success,
                        text: LANG.alert_tiang_deleted_success,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: LANG.alert_failed,
                        text: data.message || LANG.alert_tiang_delete_error
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: LANG.alert_error,
                    text: LANG.alert_server_connection_failed
                });
            });
        }
    });
}

// Fungsi untuk pusatkan peta ke Tiang dari modal detail
function pusatkanPetaKeTiangDetail() {
    if (window.currentDetailTiangId) {
        var tiang = networkData.tiang.find(function(t) { return t.id == window.currentDetailTiangId; });
        if (tiang) {
            var coords = tiang.coordinates.split(',').map(parseFloat);
            map.setView(coords, 18);
            tutupModalDetailTiang();
        }
    }
}

// ==================== END FUNGSI TIANG ====================

// ==================== FUNGSI KABEL ====================

// Fungsi untuk menampilkan notifikasi toast
function tampilkanNotifikasi(pesan, tipe) {
    // tipe: 'success', 'error', 'warning', 'info'
    var bgColor = '#10b981'; // success - hijau
    if (tipe === 'error') bgColor = '#ef4444';
    else if (tipe === 'warning') bgColor = '#f59e0b';
    else if (tipe === 'info') bgColor = '#3b82f6';
    
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: tipe || 'info',
        title: pesan,
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
        background: bgColor,
        color: '#fff',
        customClass: {
            popup: 'colored-toast'
        }
    });
}

// Fungsi untuk mengaktifkan mode kabel
function aktifkanModeKabel() {
    isKabelMode = true;
    kabelCheckpoints = [];
    kabelStartDevice = null;
    kabelEndDevice = null;
    
    // Tampilkan indikator
    var indicator = document.getElementById('kabelModeIndicator');
    if (indicator) {
        indicator.classList.add('active');
        document.getElementById('kabelModeText').textContent = LANG.kabel_mode_click_first_device;
        document.getElementById('kabelCheckpointCount').textContent = LANG.kabel_mode_checkpoint + '0';
        document.getElementById('kabelDistanceCount').textContent = LANG.kabel_mode_distance + '0 m';
        document.getElementById('btnKabelUndo').disabled = true;
    }
    
    // Ubah cursor
    document.getElementById('map').style.cursor = 'crosshair';
    
    // Tutup semua popup yang terbuka
    map.closePopup();
    
    // Disable popup untuk semua marker saat mode kabel
    disableAllPopups();
    
    // Disable dan pudarkan semua kabel existing
    disableAllKabels();
    
    // Tambahkan event listener untuk klik map
    setTimeout(function() {
        map.on('click', onMapClickKabel);
    }, 300);
    
    // Tambahkan listener untuk semua marker device
    tambahListenerDeviceKabel();
}

// Fungsi disable semua popup
function disableAllPopups() {
    ['routers', 'olts', 'odcs', 'odps', 'tiang', 'customers'].forEach(function(groupName) {
        if (markersGroup[groupName]) {
            markersGroup[groupName].eachLayer(function(layer) {
                if (layer.getPopup && layer.getPopup()) {
                    layer._kabelPopupContent = layer.getPopup().getContent();
                    layer.closePopup();
                    layer.unbindPopup();
                }
            });
        }
    });
}

// Fungsi enable semua popup
function enableAllPopups() {
    ['routers', 'olts', 'odcs', 'odps', 'tiang', 'customers'].forEach(function(groupName) {
        if (markersGroup[groupName]) {
            markersGroup[groupName].eachLayer(function(layer) {
                if (layer._kabelPopupContent) {
                    layer.bindPopup(layer._kabelPopupContent);
                    delete layer._kabelPopupContent;
                }
            });
        }
    });
}

// Fungsi untuk disable semua kabel saat mode kabel aktif
function disableAllKabels() {
    if (!networkData.kabel) return;
    
    networkData.kabel.forEach(function(kabel) {
        // Simpan opacity asli
        if (kabel.polyline) {
            kabel._originalOpacity = kabel.polyline.options.opacity;
            kabel.polyline.setStyle({ opacity: 0.2 });
            
            // Stop animasi blink jika ada
            var polylineElement = kabel.polyline.getElement();
            if (polylineElement) {
                if (polylineElement.classList.contains('kabel-offline-blink')) {
                    kabel._hadOfflineBlink = true;
                    polylineElement.classList.remove('kabel-offline-blink');
                }
                if (polylineElement.classList.contains('kabel-isolir-blink')) {
                    kabel._hadIsolirBlink = true;
                    polylineElement.classList.remove('kabel-isolir-blink');
                }
            }
        }
        // Disable hitbox
        if (kabel.hitboxPolyline) {
            kabel.hitboxPolyline.setStyle({ weight: 0 });
            kabel.hitboxPolyline.closeTooltip();
            kabel.hitboxPolyline.unbindTooltip();
        }
    });
}

// Fungsi untuk enable semua kabel setelah mode kabel selesai
function enableAllKabels() {
    if (!networkData.kabel) return;
    
    networkData.kabel.forEach(function(kabel) {
        // Kembalikan opacity asli
        if (kabel.polyline) {
            var originalOpacity = kabel._originalOpacity || 0.8;
            kabel.polyline.setStyle({ opacity: originalOpacity });
            
            // Restore animasi blink jika sebelumnya ada
            setTimeout(function() {
                var polylineElement = kabel.polyline.getElement();
                if (polylineElement) {
                    if (kabel._hadOfflineBlink) {
                        polylineElement.classList.add('kabel-offline-blink');
                        delete kabel._hadOfflineBlink;
                    }
                    if (kabel._hadIsolirBlink) {
                        polylineElement.classList.add('kabel-isolir-blink');
                        delete kabel._hadIsolirBlink;
                    }
                }
            }, 50);
        }
        // Enable hitbox
        if (kabel.hitboxPolyline) {
            kabel.hitboxPolyline.setStyle({ weight: 20 });
            var tooltipText = (kabel.name || LANG.kabel_default_name + kabel.id) + ' (' + formatMeter(kabel.panjang) + ')';
            kabel.hitboxPolyline.bindTooltip(tooltipText, { permanent: false, direction: 'center' });
        }
    });
}

// Fungsi untuk membatalkan mode kabel
function batalkanModeKabel() {
    // Reset highlighted markers ke tampilan asli
    resetAllHighlightedMarkers();
    
    isKabelMode = false;
    kabelStartDevice = null;
    kabelEndDevice = null;
    kabelCheckpoints = [];
    
    // Sembunyikan indikator
    var indicator = document.getElementById('kabelModeIndicator');
    if (indicator) {
        indicator.classList.remove('active');
    }
    
    // Kembalikan cursor
    document.getElementById('map').style.cursor = '';
    
    // Hapus event listener
    map.off('click', onMapClickKabel);
    
    // Hapus preview lines
    hapusKabelPreview();
    
    // Hapus checkpoint markers
    hapusCheckpointMarkers();
    
    // Hapus listener device
    hapusListenerDeviceKabel();
    
    // Enable kembali popup untuk semua marker
    enableAllPopups();
    
    // Enable dan kembalikan warna semua kabel
    enableAllKabels();
}

// Fungsi untuk menambah listener ke semua device marker
function tambahListenerDeviceKabel() {
    // Untuk setiap layer group device
    ['routers', 'olts', 'odcs', 'odps', 'customers'].forEach(function(groupName) {
        markersGroup[groupName].eachLayer(function(layer) {
            if (layer._icon) {
                layer._icon.style.cursor = 'pointer';
            }
            layer.on('click', onDeviceClickKabel);
        });
    });
    
    // Tambahkan listener khusus untuk Tiang (sebagai checkpoint)
    if (markersGroup.tiang) {
        markersGroup.tiang.eachLayer(function(layer) {
            if (layer._icon) {
                layer._icon.style.cursor = 'pointer';
            }
            layer.on('click', onTiangClickKabel);
        });
    }
}

// Fungsi untuk menghapus listener dari device marker
function hapusListenerDeviceKabel() {
    ['routers', 'olts', 'odcs', 'odps', 'customers'].forEach(function(groupName) {
        markersGroup[groupName].eachLayer(function(layer) {
            if (layer._icon) {
                layer._icon.style.cursor = '';
            }
            layer.off('click', onDeviceClickKabel);
        });
    });
    
    // Hapus listener dari Tiang
    if (markersGroup.tiang) {
        markersGroup.tiang.eachLayer(function(layer) {
            if (layer._icon) {
                layer._icon.style.cursor = '';
            }
            layer.off('click', onTiangClickKabel);
        });
    }
}

// Fungsi untuk disable semua popup marker saat mode kabel
function disableAllPopups() {
    ['routers', 'olts', 'odcs', 'odps', 'tiang', 'customers'].forEach(function(groupName) {
        markersGroup[groupName].eachLayer(function(layer) {
            if (layer.getPopup()) {
                layer.unbindPopup();
                layer._popupBackup = layer._popup;
            }
        });
    });
}

// Fungsi untuk enable kembali semua popup marker
function enableAllPopups() {
    ['routers', 'olts', 'odcs', 'odps', 'tiang', 'customers'].forEach(function(groupName) {
        markersGroup[groupName].eachLayer(function(layer) {
            if (layer._popupBackup) {
                layer.bindPopup(layer._popupBackup);
                delete layer._popupBackup;
            }
        });
    });
}

// Handler ketika device diklik saat mode kabel
function onDeviceClickKabel(e) {
    L.DomEvent.stopPropagation(e);
    
    var layer = e.target;
    var deviceData = getDeviceDataFromLayer(layer);
    
    if (!deviceData) {
        tampilkanNotifikasi(LANG.kabel_mode_device_invalid, 'error');
        return;
    }
    
    if (!kabelStartDevice) {
        // Set device pertama
        kabelStartDevice = deviceData;
        kabelStartDevice.layer = layer; // Simpan reference layer
        kabelCheckpoints.push(deviceData.coordinates);
        
        document.getElementById('kabelModeText').textContent = LANG.kabel_mode_click_for_checkpoint;
        document.getElementById('btnKabelUndo').disabled = false;
        
        // Highlight marker start (ubah tampilan marker asli)
        highlightDeviceMarker(layer, 'start');
        
        tampilkanNotifikasi(LANG.kabel_mode_start_device + deviceData.name, 'success');
        
        updateKabelInfo();
    } else {
        // Set device kedua (end)
        // Pastikan bukan device yang sama
        if (deviceData.type === kabelStartDevice.type && deviceData.id === kabelStartDevice.id) {
            tampilkanNotifikasi(LANG.kabel_mode_same_device_error, 'error');
            return;
        }
        
        // Cek apakah kabel sudah ada
        cekKabelExists(kabelStartDevice, deviceData, function(exists) {
            if (exists) {
                tampilkanNotifikasi(LANG.kabel_mode_cable_exists, 'error');
                return;
            }
            
            kabelEndDevice = deviceData;
            kabelEndDevice.layer = layer; // Simpan reference layer
            kabelCheckpoints.push(deviceData.coordinates);
            
            // Highlight marker end (ubah tampilan marker asli)
            highlightDeviceMarker(layer, 'end');
            
            // Buka modal untuk menyimpan
            bukaModalKabel();
        });
    }
}

// Handler ketika map diklik saat mode kabel (untuk checkpoint)
function onMapClickKabel(e) {
    if (!isKabelMode) return;
    
    // Jika belum ada start device, abaikan klik di map
    if (!kabelStartDevice) {
        tampilkanNotifikasi(LANG.kabel_mode_click_device_first, 'warning');
        return;
    }
    
    var lat = e.latlng.lat;
    var lng = e.latlng.lng;
    var coord = [lat, lng];
    
    // Cek apakah klik pada Tiang (Tiang dianggap checkpoint)
    var clickedTiang = cekKlikPadaTiang(e.latlng);
    if (clickedTiang) {
        coord = clickedTiang.coordinates.split(',').map(parseFloat);
        tampilkanNotifikasi(LANG.kabel_mode_checkpoint_at_pole + clickedTiang.name, 'info');
    }
    
    // Tambah checkpoint
    kabelCheckpoints.push(coord);
    
    // Tambah marker checkpoint
    tambahCheckpointMarker(coord, 'checkpoint');
    
    // Update preview line
    updateKabelPreview();
    
    // Update info
    updateKabelInfo();
    
    document.getElementById('btnKabelUndo').disabled = false;
}

// Handler ketika Tiang diklik saat mode kabel (sebagai checkpoint)
function onTiangClickKabel(e) {
    L.DomEvent.stopPropagation(e);
    
    if (!isKabelMode) return;
    
    // Jika belum ada start device, tiang tidak bisa jadi start
    if (!kabelStartDevice) {
        tampilkanNotifikasi(LANG.kabel_mode_select_device_first_pole_checkpoint, 'warning');
        return;
    }
    
    // Ambil data tiang dari layer
    var layer = e.target;
    var latlng = layer.getLatLng();
    var tiangData = null;
    
    // Cari tiang yang cocok dengan koordinat
    if (networkData.tiang) {
        tiangData = networkData.tiang.find(function(t) {
            var coords = t.coordinates.split(',').map(parseFloat);
            return Math.abs(coords[0] - latlng.lat) < 0.0001 && Math.abs(coords[1] - latlng.lng) < 0.0001;
        });
    }
    
    if (!tiangData) {
        tampilkanNotifikasi(LANG.kabel_mode_pole_data_not_found, 'error');
        return;
    }
    
    var coord = tiangData.coordinates.split(',').map(parseFloat);
    
    // Tambah sebagai checkpoint
    kabelCheckpoints.push(coord);
    
    // Tambah marker checkpoint khusus tiang
    tambahCheckpointMarker(coord, 'tiang');
    
    // Update preview line
    updateKabelPreview();
    
    // Update info
    updateKabelInfo();
    
    document.getElementById('btnKabelUndo').disabled = false;
    
    tampilkanNotifikasi(LANG.kabel_mode_checkpoint_at_pole + tiangData.name, 'info');
}
// Fungsi untuk highlight device marker (start/end)
function highlightDeviceMarker(layer, tipe) {
    if (!layer || !layer._icon) return;
    
    // Simpan icon asli untuk di-restore nanti
    if (!layer._originalIconHtml) {
        layer._originalIconHtml = layer._icon.innerHTML;
    }
    
    var iconElement = layer._icon.querySelector('div');
    if (!iconElement) return;
    
    if (tipe === 'start') {
        // Tambahkan overlay hijau dengan icon play
        iconElement.classList.add('kabel-device-highlight-start');
        
        // Tambah icon play overlay
        var overlay = document.createElement('div');
        overlay.className = 'kabel-device-overlay kabel-device-overlay-start';
        overlay.innerHTML = '<i class="fa fa-play"></i>';
        iconElement.appendChild(overlay);
        
    } else if (tipe === 'end') {
        // Tambahkan overlay merah dengan icon flag
        iconElement.classList.add('kabel-device-highlight-end');
        
        // Tambah icon flag overlay
        var overlay = document.createElement('div');
        overlay.className = 'kabel-device-overlay kabel-device-overlay-end';
        overlay.innerHTML = '<i class="fa fa-flag-checkered"></i>';
        iconElement.appendChild(overlay);
    }
}

// Fungsi untuk reset device marker ke tampilan asli
function resetDeviceMarker(layer) {
    if (!layer || !layer._icon) return;
    
    var iconElement = layer._icon.querySelector('div');
    if (!iconElement) return;
    
    // Hapus class highlight
    iconElement.classList.remove('kabel-device-highlight-start');
    iconElement.classList.remove('kabel-device-highlight-end');
    
    // Hapus overlay
    var overlays = iconElement.querySelectorAll('.kabel-device-overlay');
    overlays.forEach(function(o) {
        o.remove();
    });
}

// Fungsi untuk reset semua highlighted markers
function resetAllHighlightedMarkers() {
    if (kabelStartDevice && kabelStartDevice.layer) {
        resetDeviceMarker(kabelStartDevice.layer);
    }
    if (kabelEndDevice && kabelEndDevice.layer) {
        resetDeviceMarker(kabelEndDevice.layer);
    }
}

// Fungsi untuk cek apakah klik pada Tiang
function cekKlikPadaTiang(latlng) {
    var result = null;
    var minDist = 20; // pixel threshold
    
    if (networkData.tiang) {
        networkData.tiang.forEach(function(t) {
            var coords = t.coordinates.split(',').map(parseFloat);
            var tiangLatLng = L.latLng(coords[0], coords[1]);
            var point = map.latLngToContainerPoint(tiangLatLng);
            var clickPoint = map.latLngToContainerPoint(latlng);
            var dist = point.distanceTo(clickPoint);
            
            if (dist < minDist) {
                minDist = dist;
                result = t;
            }
        });
    }
    
    return result;
}

// Fungsi untuk undo checkpoint terakhir
function undoKabelCheckpoint() {
    if (kabelCheckpoints.length <= 1) {
        // Jika hanya start device, cancel semua
        batalkanModeKabel();
        return;
    }
    
    // Hapus checkpoint terakhir
    kabelCheckpoints.pop();
    
    // Hapus marker terakhir
    if (kabelCheckpointMarkers.length > 0) {
        var lastMarker = kabelCheckpointMarkers.pop();
        map.removeLayer(lastMarker);
    }
    
    // Update preview
    updateKabelPreview();
    
    // Update info
    updateKabelInfo();
    
    if (kabelCheckpoints.length <= 1) {
        document.getElementById('btnKabelUndo').disabled = true;
    }
}

// Fungsi untuk menambah marker checkpoint
function tambahCheckpointMarker(coord, tipe) {
    // Untuk start dan end, kita tidak buat marker baru (sudah di-highlight langsung di marker device)
    if (tipe === 'start' || tipe === 'end') {
        return; // Tidak perlu marker baru
    }
    
    var iconHtml = '<div class="kabel-checkpoint-marker kabel-checkpoint-normal"><i class="fa fa-circle"></i></div>';
    var iconSize = [14, 14];
    var iconAnchor = [7, 7];
    
    if (tipe === 'tiang') {
        // Tiang checkpoint - highlight biru
        iconHtml = '<div class="kabel-checkpoint-marker kabel-checkpoint-tiang"><i class="fa fa-check"></i></div>';
        iconSize = [18, 32];
        iconAnchor = [9, 32];
    }
    
    var icon = L.divIcon({
        className: 'custom-div-icon',
        html: iconHtml,
        iconSize: iconSize,
        iconAnchor: iconAnchor
    });
    
    var marker = L.marker(coord, { icon: icon }).addTo(map);
    kabelCheckpointMarkers.push(marker);
}

// Fungsi untuk hapus semua checkpoint markers
function hapusCheckpointMarkers() {
    kabelCheckpointMarkers.forEach(function(m) {
        map.removeLayer(m);
    });
    kabelCheckpointMarkers = [];
}

// Fungsi untuk update preview line kabel
function updateKabelPreview() {
    // Hapus preview lama
    hapusKabelPreview();
    
    if (kabelCheckpoints.length < 2) return;
    
    // Buat polyline preview
    var line = L.polyline(kabelCheckpoints, {
        color: '#78716c',
        weight: 3,
        opacity: 0.7,
        dashArray: '10, 10'
    }).addTo(map);
    
    kabelPreviewLines.push(line);
}

// Fungsi untuk hapus preview line
function hapusKabelPreview() {
    kabelPreviewLines.forEach(function(line) {
        map.removeLayer(line);
    });
    kabelPreviewLines = [];
}

// Fungsi untuk update info kabel (checkpoint count, distance)
function updateKabelInfo() {
    var checkpointCount = kabelCheckpoints.length > 0 ? kabelCheckpoints.length - 1 : 0;
    var totalDistance = hitungTotalJarak(kabelCheckpoints);
    
    document.getElementById('kabelCheckpointCount').textContent = LANG.kabel_mode_checkpoint + checkpointCount;
    document.getElementById('kabelDistanceCount').textContent = LANG.kabel_mode_distance + formatMeter(totalDistance);
}

// Fungsi untuk menghitung total jarak dari array koordinat
function hitungTotalJarak(coords) {
    var total = 0;
    for (var i = 1; i < coords.length; i++) {
        var from = L.latLng(coords[i-1][0], coords[i-1][1]);
        var to = L.latLng(coords[i][0], coords[i][1]);
        total += from.distanceTo(to);
    }
    return total;
}

// Fungsi untuk mendapatkan data device dari layer marker
function getDeviceDataFromLayer(layer) {
    var latlng = layer.getLatLng();
    var lat = latlng.lat;
    var lng = latlng.lng;
    
    // PERBAIKAN: Cek apakah marker punya customerData (untuk customer)
    if (layer.options && layer.options.customerData) {
        var c = layer.options.customerData;
        return {
            type: 'customer',
            id: c.id,
            name: c.name || 'Unknown',
            coordinates: [lat, lng]
        };
    }
    
    // PERBAIKAN: Cek apakah marker punya deviceData (untuk device lain)
    if (layer.deviceData) {
        return layer.deviceData;
    }
    
    var type = null;
    var id = null;
    var name = null;
    
    // Fungsi untuk cek koordinat cocok (dengan toleransi lebih ketat)
    function coordsMatch(coordStr) {
        var coords = coordStr.split(',').map(parseFloat);
        return Math.abs(coords[0] - lat) < 0.000001 && Math.abs(coords[1] - lng) < 0.000001;
    }
    
    // Cari di routers
    if (networkData.routers) {
        for (var i = 0; i < networkData.routers.length; i++) {
            var r = networkData.routers[i];
            if (r.coordinates && coordsMatch(r.coordinates)) {
                type = 'router';
                id = r.id;
                name = r.name;
                break;
            }
        }
    }
    
    // Cari di olts
    if (!type && networkData.olts) {
        for (var i = 0; i < networkData.olts.length; i++) {
            var o = networkData.olts[i];
            if (o.coordinates && coordsMatch(o.coordinates)) {
                type = 'olt';
                id = o.id;
                name = o.name;
                break;
            }
        }
    }
    
    // Cari di odcs
    if (!type && networkData.odcs) {
        for (var i = 0; i < networkData.odcs.length; i++) {
            var o = networkData.odcs[i];
            if (o.coordinates && coordsMatch(o.coordinates)) {
                type = 'odc';
                id = o.id;
                name = o.name;
                break;
            }
        }
    }
    
    // Cari di odps
    if (!type && networkData.odps) {
        for (var i = 0; i < networkData.odps.length; i++) {
            var o = networkData.odps[i];
            if (o.coordinates && coordsMatch(o.coordinates)) {
                type = 'odp';
                id = o.id;
                name = o.name;
                break;
            }
        }
    }
    
    // Cari di customers
    if (!type && networkData.customers) {
        for (var i = 0; i < networkData.customers.length; i++) {
            var c = networkData.customers[i];
            if (c.coordinates && coordsMatch(c.coordinates)) {
                type = 'customer';
                id = c.id;
                name = c.name;
                break;
            }
        }
    }
    
    if (!type || !id) return null;
    
    return {
        type: type,
        id: id,
        name: name || 'Unknown',
        coordinates: [lat, lng]
    };
}

// Fungsi untuk cek apakah kabel sudah ada
function cekKabelExists(device1, device2, callback) {
    fetch(baseUrl + 'plugin/network_mapping/check-kabel-exists', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            device_1_type: device1.type,
            device_1_id: device1.id,
            device_2_type: device2.type,
            device_2_id: device2.id
        })
    })
    .then(response => response.json())
    .then(data => {
        callback(data.exists);
    })
    .catch(error => {
        console.error('Error:', error);
        callback(false);
    });
}

// Fungsi untuk buka modal kabel
function bukaModalKabel() {
    // Sembunyikan indikator mode
    document.getElementById('kabelModeIndicator').classList.remove('active');
    map.off('click', onMapClickKabel);
    document.getElementById('map').style.cursor = '';
    
    // Isi form dengan data
    document.getElementById('kabelDevice1Type').value = kabelStartDevice.type;
    document.getElementById('kabelDevice1Id').value = kabelStartDevice.id;
    document.getElementById('kabelDevice2Type').value = kabelEndDevice.type;
    document.getElementById('kabelDevice2Id').value = kabelEndDevice.id;
    document.getElementById('kabelCoordinatesPath').value = JSON.stringify(kabelCheckpoints);
    
    // Display
    document.getElementById('kabelDariDisplay').value = ucfirst(kabelStartDevice.type) + ': ' + kabelStartDevice.name;
    document.getElementById('kabelKeDisplay').value = ucfirst(kabelEndDevice.type) + ': ' + kabelEndDevice.name;
    
    // Hitung jarak
    var totalDistance = hitungTotalJarak(kabelCheckpoints);
    document.getElementById('kabelPanjang').value = totalDistance.toFixed(1);
    
    // Reset sambungan
    document.getElementById('kabelJumlahSambungan').value = 0;
    document.getElementById('kabelSambunganContainer').innerHTML = '<p class="text-muted" style="text-align: center; padding: 10px;"><i class="fa fa-info-circle"></i> ' + LANG.kabel_no_connections + '</p>';
    
    // Tampilkan modal
    document.getElementById('modalKabelOverlay').classList.add('active');
}

// Helper function ucfirst
function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Fungsi untuk tutup modal kabel
function tutupModalKabel() {
    document.getElementById('modalKabelOverlay').classList.remove('active');
    document.getElementById('formKabel').reset();
    
    // Bersihkan semua
    batalkanModeKabel();
}

// Fungsi untuk ubah jumlah sambungan
function ubahJumlahSambungan(delta) {
    var input = document.getElementById('kabelJumlahSambungan');
    var nilai = parseInt(input.value) || 0;
    nilai += delta;
    if (nilai < 0) nilai = 0;
    if (nilai > 20) nilai = 20;
    input.value = nilai;
    
    generateSambunganFields(nilai);
}

// Fungsi untuk generate form sambungan
function generateSambunganFields(jumlah) {
    var container = document.getElementById('kabelSambunganContainer');
    
    if (jumlah <= 0) {
        container.innerHTML = '<p class="text-muted" style="text-align: center; padding: 10px;"><i class="fa fa-info-circle"></i> ' + LANG.kabel_no_connections + '</p>';
        return;
    }
    
    var device1Name = document.getElementById('kabelDariDisplay').value;
    var device2Name = document.getElementById('kabelKeDisplay').value;
    
    var html = '<div class="sambungan-fields-header" style="background: #f5f5f5; padding: 10px; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid #78716c;">' +
        '<strong style="color: #57534e;"><i class="fa fa-link"></i> ' + LANG.kabel_detail_connections + jumlah + LANG.kabel_connections + '</strong>' +
        '</div>';
    
    for (var i = 0; i < jumlah; i++) {
        html += '<div class="sambungan-field-item" style="background: #fafafa; padding: 12px; border-radius: 6px; margin-bottom: 10px; border: 1px solid #e5e7eb;">' +
            '<div style="font-weight: 600; margin-bottom: 8px; color: #4b5563;"><i class="fa fa-link" style="color: #78716c;"></i> ' + LANG.kabel_connection_number + (i+1) + '</div>' +
            '<div class="row">' +
                '<div class="col-md-4">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.kabel_from_point + '</label>' +
                        '<select class="form-control input-sm" id="sambunganTitik_' + i + '">' +
                            '<option value="device_1">' + device1Name + '</option>' +
                            '<option value="device_2">' + device2Name + '</option>' +
                        '</select>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-4">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.kabel_distance_meters + '</label>' +
                        '<input type="number" class="form-control input-sm" id="sambunganJarak_' + i + '" placeholder="' + LANG.kabel_distance_placeholder + '" step="0.1" min="0">' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-4">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.kabel_description + '</label>' +
                        '<input type="text" class="form-control input-sm" id="sambunganKeterangan_' + i + '" placeholder="' + LANG.kabel_description_placeholder + '" maxlength="20">' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
    }
    
    container.innerHTML = html;
}

// Fungsi untuk mengumpulkan data sambungan
function getSambunganData() {
    var jumlah = parseInt(document.getElementById('kabelJumlahSambungan').value) || 0;
    var data = [];
    
    for (var i = 0; i < jumlah; i++) {
        var titik = document.getElementById('sambunganTitik_' + i);
        var jarak = document.getElementById('sambunganJarak_' + i);
        var keterangan = document.getElementById('sambunganKeterangan_' + i);
        
        if (titik && jarak) {
            data.push({
                titik: titik.value,
                jarak: parseFloat(jarak.value) || 0,
                keterangan: keterangan ? keterangan.value : ''
            });
        }
    }
    
    return data;
}

// Fungsi untuk simpan kabel
function simpanKabel() {
    var formData = {
        name: document.getElementById('kabelName').value,
        device_1_type: document.getElementById('kabelDevice1Type').value,
        device_1_id: document.getElementById('kabelDevice1Id').value,
        device_2_type: document.getElementById('kabelDevice2Type').value,
        device_2_id: document.getElementById('kabelDevice2Id').value,
        coordinates_path: document.getElementById('kabelCoordinatesPath').value,
        panjang: document.getElementById('kabelPanjang').value,
        jumlah_sambungan: document.getElementById('kabelJumlahSambungan').value,
        sambungan_data: JSON.stringify(getSambunganData()),
        description: document.getElementById('kabelDescription').value
    };
    
    Swal.fire({
        title: LANG.alert_saving,
        text: LANG.alert_please_wait,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(baseUrl + 'plugin/network_mapping/save-kabel', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            tutupModalKabel();
            
            Swal.fire({
                icon: 'success',
                title: LANG.alert_success,
                text: data.message,
                confirmButtonColor: '#78716c',
                timer: 2000,
                timerProgressBar: true
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: LANG.alert_failed,
                text: data.message,
                confirmButtonColor: '#ef4444'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_error,
            text: LANG.alert_error_occurred + error.message,
            confirmButtonColor: '#ef4444'
        });
    });
}

// ==================== FUNGSI DETAIL & HAPUS KABEL ====================

// Variabel untuk detail kabel
var currentDetailKabelId = null;
var currentDetailKabelData = null;

// Fungsi untuk menampilkan detail kabel
function tampilkanDetailKabel(id) {
    var kabel = null;
    if (networkData.kabel) {
        kabel = networkData.kabel.find(function(k) {
            return k.id == id;
        });
    }
    
    if (!kabel) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_kabel_not_found,
            text: LANG.alert_kabel_not_found_text
        });
        return;
    }
    
    currentDetailKabelId = id;
    currentDetailKabelData = kabel;
    
    // Isi form dengan data kabel
    document.getElementById('detailKabelId').value = kabel.id;
    document.getElementById('detailKabelName').value = kabel.name || '';
    document.getElementById('detailKabelDari').value = kabel.device_1_type.toUpperCase() + ': ' + kabel.device_1_name;
    document.getElementById('detailKabelKe').value = kabel.device_2_type.toUpperCase() + ': ' + kabel.device_2_name;
    document.getElementById('detailKabelPanjang').value = parseFloat(kabel.panjang).toFixed(1);
    document.getElementById('detailKabelCheckpoint').value = (kabel.coordinates_path.length - 2) + LANG.kabel_checkpoint_points;
    document.getElementById('detailKabelDescription').value = kabel.description || '';
    document.getElementById('detailKabelJumlahSambungan').value = kabel.jumlah_sambungan || 0;
    
    // Generate sambungan fields
    generateDetailKabelSambunganFields(kabel.jumlah_sambungan || 0, kabel.sambungan_data || []);
    
    // Tampilkan modal
    document.getElementById('modalDetailKabelOverlay').classList.add('active');
}

// Fungsi untuk tutup modal detail kabel
function tutupModalDetailKabel() {
    document.getElementById('modalDetailKabelOverlay').classList.remove('active');
    document.getElementById('formEditKabel').reset();
    currentDetailKabelId = null;
    currentDetailKabelData = null;
}

// Fungsi untuk ubah jumlah sambungan di detail
function ubahJumlahSambunganDetail(delta) {
    var input = document.getElementById('detailKabelJumlahSambungan');
    var nilai = parseInt(input.value) || 0;
    nilai += delta;
    if (nilai < 0) nilai = 0;
    if (nilai > 20) nilai = 20;
    input.value = nilai;
    
    // Ambil data sambungan yang sudah ada
    var existingData = getDetailKabelSambunganData();
    generateDetailKabelSambunganFields(nilai, existingData);
}

// Fungsi untuk generate form sambungan di detail kabel
function generateDetailKabelSambunganFields(jumlah, existingData) {
    var container = document.getElementById('detailKabelSambunganContainer');
    
    if (jumlah <= 0) {
        container.innerHTML = '<p class="text-muted" style="text-align: center; padding: 10px;"><i class="fa fa-info-circle"></i> ' + LANG.kabel_no_connections + '</p>';
        return;
    }
    
    var device1Name = document.getElementById('detailKabelDari').value;
    var device2Name = document.getElementById('detailKabelKe').value;
    
    var html = '<div class="sambungan-fields-header" style="background: #f5f5f5; padding: 10px; border-radius: 6px; margin-bottom: 10px; border-left: 3px solid #78716c;">' +
        '<strong style="color: #57534e;"><i class="fa fa-link"></i> ' + LANG.kabel_detail_connections + jumlah + LANG.kabel_connections + '</strong>' +
        '</div>';
    
    for (var i = 0; i < jumlah; i++) {
        // Ambil data existing jika ada
        var existingTitik = (existingData && existingData[i]) ? existingData[i].titik : 'device_1';
        var existingJarak = (existingData && existingData[i]) ? existingData[i].jarak : '';
        var existingKeterangan = (existingData && existingData[i]) ? existingData[i].keterangan : '';
        
        html += '<div class="sambungan-field-item" style="background: #fafafa; padding: 12px; border-radius: 6px; margin-bottom: 10px; border: 1px solid #e5e7eb;">' +
            '<div style="font-weight: 600; margin-bottom: 8px; color: #4b5563;"><i class="fa fa-link" style="color: #78716c;"></i> ' + LANG.kabel_connection_number + (i+1) + '</div>' +
            '<div class="row">' +
                '<div class="col-md-4">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.kabel_from_point + '</label>' +
                        '<select class="form-control input-sm" id="detailSambunganTitik_' + i + '">' +
                            '<option value="device_1"' + (existingTitik === 'device_1' ? ' selected' : '') + '>' + device1Name + '</option>' +
                            '<option value="device_2"' + (existingTitik === 'device_2' ? ' selected' : '') + '>' + device2Name + '</option>' +
                        '</select>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-4">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.kabel_distance_meters + '</label>' +
                        '<input type="number" class="form-control input-sm" id="detailSambunganJarak_' + i + '" value="' + existingJarak + '" placeholder="' + LANG.kabel_distance_placeholder + '" step="0.1" min="0">' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-4">' +
                    '<div class="form-group" style="margin-bottom: 8px;">' +
                        '<label style="font-size: 12px;">' + LANG.kabel_description + '</label>' +
                        '<input type="text" class="form-control input-sm" id="detailSambunganKeterangan_' + i + '" value="' + (existingKeterangan || '') + '" placeholder="' + LANG.kabel_description_placeholder + '" maxlength="20">' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
    }
    
    container.innerHTML = html;
}

// Fungsi untuk mengumpulkan data sambungan dari form detail
function getDetailKabelSambunganData() {
    var jumlah = parseInt(document.getElementById('detailKabelJumlahSambungan').value) || 0;
    var data = [];
    
    for (var i = 0; i < jumlah; i++) {
        var titik = document.getElementById('detailSambunganTitik_' + i);
        var jarak = document.getElementById('detailSambunganJarak_' + i);
        var keterangan = document.getElementById('detailSambunganKeterangan_' + i);
        
        if (titik && jarak) {
            data.push({
                titik: titik.value,
                jarak: parseFloat(jarak.value) || 0,
                keterangan: keterangan ? keterangan.value : ''
            });
        }
    }
    
    return data;
}

// Fungsi untuk simpan edit kabel
function simpanEditKabel() {
    var formData = {
        kabel_id: document.getElementById('detailKabelId').value,
        name: document.getElementById('detailKabelName').value,
        panjang: document.getElementById('detailKabelPanjang').value,
        jumlah_sambungan: document.getElementById('detailKabelJumlahSambungan').value,
        sambungan_data: JSON.stringify(getDetailKabelSambunganData()),
        description: document.getElementById('detailKabelDescription').value
    };
    
    Swal.fire({
        title: LANG.alert_saving,
        text: LANG.alert_please_wait,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: function() {
            Swal.showLoading();
        }
    });
    
    fetch(baseUrl + 'plugin/network_mapping/update-kabel', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(formData)
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: LANG.alert_success,
                text: data.message,
                confirmButtonColor: '#78716c',
                timer: 2000,
                timerProgressBar: true
            }).then(function() {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: LANG.alert_failed,
                text: data.message,
                confirmButtonColor: '#ef4444'
            });
        }
    })
    .catch(function(error) {
        Swal.fire({
            icon: 'error',
            title: LANG.alert_error,
            text: LANG.alert_error_occurred + error.message,
            confirmButtonColor: '#ef4444'
        });
    });
}

// Fungsi untuk hapus kabel dari detail modal
function hapusKabelDariDetail() {
    if (!currentDetailKabelId) return;
    hapusKabel(currentDetailKabelId);
}

// Fungsi untuk hapus kabel dari map popup
function hapusKabelDariMap(id) {
    hapusKabel(id);
}

// Fungsi utama hapus kabel
function hapusKabel(id) {
    Swal.fire({
        title: LANG.alert_delete_kabel_title,
        text: LANG.alert_delete_kabel_text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-trash"></i> ' + LANG.alert_yes_delete,
        cancelButtonText: LANG.alert_cancel,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: LANG.alert_deleting,
                text: LANG.alert_please_wait,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch(baseUrl + 'plugin/network_mapping/delete-kabel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    kabel_id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: LANG.alert_success,
                        text: LANG.alert_kabel_deleted_success,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: LANG.alert_failed,
                        text: data.message || LANG.alert_kabel_delete_error
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: LANG.alert_error,
                    text: LANG.alert_server_connection_failed
                });
            });
        }
    });
}

// ==================== END FUNGSI KABEL ====================

// Inisialisasi saat halaman dimuat
function inisialisasiPeta() {
    if (typeof L !== 'undefined') {
        console.log(LANG.init_leaflet_loaded);
        dapatkanLokasi();
    } else {
        console.error(LANG.init_leaflet_not_loaded);
        document.getElementById('loading').innerHTML = '<i class="fa fa-exclamation-triangle"></i><br><br>' + LANG.init_loading_map_library;
        // Coba ulang setelah 1 detik
        setTimeout(inisialisasiPeta, 1000);
    }
}

// Mulai inisialisasi saat halaman dimuat
window.onload = function() {
    inisialisasiPeta();
}

// ==================== FUNGSI HOMEPASS ====================

// Variabel untuk homepass
var currentDetailHomepassId = null;

// Buka modal tambah homepass
function bukaModalHomepass(coordinates) {
    document.getElementById('homepassCoordinates').value = coordinates;
    document.getElementById('homepassCoordinatesDisplay').value = coordinates;
    document.getElementById('homepassTanggal').value = new Date().toISOString().split('T')[0];
    document.getElementById('modalHomepassOverlay').classList.add('active');
}

// Tutup modal homepass
function tutupModalHomepass() {
    document.getElementById('modalHomepassOverlay').classList.remove('active');
    document.getElementById('formHomepass').reset();
    
    // Reset foto preview
    hapusFotoHomepassAddPreview();
    
    // Hapus temp marker jika ada
    if (tempMarker) {
        map.removeLayer(tempMarker);
        tempMarker = null;
    }
}

// Pilih lokasi homepass di peta
function pilihLokasiHomepass() {
    tutupModalHomepass();
    aktifkanModePick('homepass');
}

// Simpan homepass baru
function simpanHomepass() {
    var name = document.getElementById('homepassName').value;
    var coordinates = document.getElementById('homepassCoordinates').value;
    
    if (!coordinates) {
        Swal.fire({ icon: 'error', title: LANG.alert_error, text: LANG.alert_location_not_selected });
        return;
    }
    
    var fotoInput = document.getElementById('addHomepassFoto');
    
    // Validasi ukuran foto (max 5MB)
    if (fotoInput && fotoInput.files.length > 0) {
        var fileSize = fotoInput.files[0].size;
        var maxSize = 5 * 1024 * 1024; // 5MB
        if (fileSize > maxSize) {
            Swal.fire({ icon: 'error', title: LANG.alert_error, text: LANG.alert_photo_max_5mb });
            return;
        }
    }
    
    Swal.fire({
        title: LANG.alert_saving,
        text: LANG.alert_please_wait,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: function() { Swal.showLoading(); }
    });
    
    // Gunakan FormData untuk upload file
    var formData = new FormData();
    formData.append('name', name);
    formData.append('coordinates', coordinates);
    formData.append('address', document.getElementById('homepassAddress').value);
    formData.append('phone', document.getElementById('homepassPhone').value);
    formData.append('status', document.getElementById('homepassStatus').value);
    formData.append('kategori', document.getElementById('homepassKategori').value);
    formData.append('catatan', document.getElementById('homepassCatatan').value);
    formData.append('surveyor', document.getElementById('homepassSurveyor').value);
    formData.append('tanggal_survey', document.getElementById('homepassTanggal').value);
    
    // Tambahkan foto jika ada
    if (fotoInput && fotoInput.files.length > 0) {
        formData.append('foto_homepass', fotoInput.files[0]);
    }
    
    fetch(baseUrl + 'plugin/network_mapping/save-homepass', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: LANG.alert_success,
                text: data.message,
                confirmButtonColor: '#8b5cf6'
            }).then(function() {
                location.reload();
            });
        } else {
            Swal.fire({ icon: 'error', title: LANG.alert_failed, text: data.message });
        }
    })
    .catch(function(error) {
        Swal.fire({ icon: 'error', title: LANG.alert_error, text: LANG.alert_error_occurred + error.message });
    });
}

// Tampilkan detail homepass
function tampilkanDetailHomepass(id) {
    var homepass = null;
    if (networkData.homepass) {
        homepass = networkData.homepass.find(function(h) {
            return h.id == id;
        });
    }
    
    if (!homepass) {
        Swal.fire({ icon: 'error', title: LANG.alert_error, text: LANG.alert_homepass_not_found });
        return;
    }
    
    currentDetailHomepassId = id;
    
    // Isi form
    document.getElementById('detailHomepassId').value = homepass.id;
    document.getElementById('detailHomepassCoordinates').value = homepass.coordinates;
    document.getElementById('detailHomepassCoordinatesDisplay').value = homepass.coordinates;
    document.getElementById('detailHomepassName').value = homepass.name;
    document.getElementById('detailHomepassAddress').value = homepass.address || '';
    document.getElementById('detailHomepassPhone').value = homepass.phone || '';
    document.getElementById('detailHomepassKategori').value = homepass.kategori || 'Rumah';
    document.getElementById('detailHomepassStatus').value = homepass.status || 'Prospek';
    document.getElementById('detailHomepassTanggal').value = homepass.tanggal_survey || '';
    document.getElementById('detailHomepassSurveyor').value = homepass.surveyor || '';
    document.getElementById('detailHomepassCatatan').value = homepass.catatan || '';
    
    // Handle foto
    document.getElementById('editHomepassHapusFoto').value = '0';
    document.getElementById('editHomepassFoto').value = '';
    
    var fotoPlaceholder = document.getElementById('editHomepassFotoPlaceholder');
    var fotoPreview = document.getElementById('editHomepassFotoPreview');
    var fotoPreviewImg = document.getElementById('editHomepassFotoPreviewImg');
    
    if (homepass.foto && homepass.foto_url) {
        fotoPreviewImg.src = homepass.foto_url;
        fotoPlaceholder.style.display = 'none';
        fotoPreview.style.display = 'block';
    } else {
        fotoPlaceholder.style.display = 'flex';
        fotoPreview.style.display = 'none';
        fotoPreviewImg.src = '';
    }
    
    document.getElementById('modalDetailHomepassOverlay').classList.add('active');
}

// Tutup modal detail homepass
function tutupModalDetailHomepass() {
    document.getElementById('modalDetailHomepassOverlay').classList.remove('active');
    currentDetailHomepassId = null;
    
    // Clear remap data
    window.remapHomepassId = null;
    window.remapHomepassData = null;
    
    // Batalkan mode pick jika masih aktif
    if (isPickMode && pickModeType === 'remap-homepass') {
        batalkanModePick();
    }
    
    // Hapus temp marker jika ada
    if (tempMarker) {
        map.removeLayer(tempMarker);
        tempMarker = null;
    }
}

// Update homepass
function updateHomepass() {
    var homepassId = document.getElementById('detailHomepassId').value;
    var name = document.getElementById('detailHomepassName').value;
    var coordinates = document.getElementById('detailHomepassCoordinates').value;
    
    var hapusFoto = document.getElementById('editHomepassHapusFoto').value;
    var fotoInput = document.getElementById('editHomepassFoto');
    
    // Validasi ukuran foto
    if (fotoInput && fotoInput.files.length > 0) {
        if (fotoInput.files[0].size > 5 * 1024 * 1024) {
            Swal.fire({ icon: 'error', title: LANG.alert_error, text: LANG.alert_photo_max_5mb });
            return;
        }
    }
    
    Swal.fire({
        title: LANG.alert_saving,
        text: LANG.alert_please_wait,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: function() { Swal.showLoading(); }
    });
    
    // Gunakan FormData untuk upload file
    var formData = new FormData();
    formData.append('homepass_id', homepassId);
    formData.append('name', name);
    formData.append('coordinates', coordinates);
    formData.append('address', document.getElementById('detailHomepassAddress').value);
    formData.append('phone', document.getElementById('detailHomepassPhone').value);
    formData.append('status', document.getElementById('detailHomepassStatus').value);
    formData.append('kategori', document.getElementById('detailHomepassKategori').value);
    formData.append('catatan', document.getElementById('detailHomepassCatatan').value);
    formData.append('surveyor', document.getElementById('detailHomepassSurveyor').value);
    formData.append('tanggal_survey', document.getElementById('detailHomepassTanggal').value);
    formData.append('hapus_foto', hapusFoto);
    
    // Tambahkan foto jika ada
    if (fotoInput && fotoInput.files.length > 0) {
        formData.append('foto_homepass', fotoInput.files[0]);
    }
    
    fetch(baseUrl + 'plugin/network_mapping/update-homepass', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: LANG.alert_success,
                text: data.message,
                confirmButtonColor: '#8b5cf6'
            }).then(function() {
                location.reload();
            });
        } else {
            Swal.fire({ icon: 'error', title: LANG.alert_failed, text: data.message });
        }
    })
    .catch(function(error) {
        Swal.fire({ icon: 'error', title: LANG.alert_error, text: LANG.alert_error_occurred + error.message });
    });
}

// Hapus homepass
function hapusHomepass() {
    if (!currentDetailHomepassId) return;
    
    Swal.fire({
        title: LANG.alert_delete_homepass_title,
        text: LANG.alert_delete_homepass_text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: LANG.alert_yes_delete,
        cancelButtonText: LANG.alert_cancel,
        confirmButtonColor: '#ef4444',
        reverseButtons: true
    }).then(function(result) {
        if (result.isConfirmed) {
            fetch(baseUrl + 'plugin/network_mapping/delete-homepass', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ homepass_id: currentDetailHomepassId })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: LANG.alert_success,
                        text: data.message
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: LANG.alert_failed, text: data.message });
                }
            });
        }
    });
}

// Remap lokasi homepass
function remapHomepass() {
    tutupModalDetailHomepass();
    aktifkanModePick('remap-homepass');
}

// Buka kembali modal detail homepass dengan koordinat baru
function bukaKembaliModalDetailHomepass(coordinates) {
    document.getElementById('detailHomepassCoordinates').value = coordinates;
    document.getElementById('detailHomepassCoordinatesDisplay').value = coordinates;
    document.getElementById('modalDetailHomepassOverlay').classList.add('active');
    
    Swal.fire({
        icon: 'success',
        title: LANG.alert_location_updated,
        text: LANG.alert_location_updated_text,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000
    });
}
// ==================== SEARCH AUTOCOMPLETE FUNCTIONS ====================

var searchTimeout = null;
var searchDropdownVisible = false;
var selectedSearchIndex = -1;
var searchResults = [];

// Event ketika user mengetik di search input
function onSearchInputKeyup(event) {
    var input = document.getElementById('searchInput');
    var query = input.value.trim();
    var btnClear = document.getElementById('btnClearSearch');
    
    // Tampilkan/sembunyikan tombol clear
    btnClear.style.display = query.length > 0 ? 'block' : 'none';
    
    // Handle arrow keys dan enter
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        navigateSearchResults(1);
        return;
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        navigateSearchResults(-1);
        return;
    } else if (event.key === 'Enter') {
        event.preventDefault();
        selectCurrentSearchResult();
        return;
    } else if (event.key === 'Escape') {
        hideSearchDropdown();
        input.blur();
        return;
    }
    
    // Debounce search
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        hideSearchDropdown();
        return;
    }
    
    searchTimeout = setTimeout(function() {
        performSearch(query);
    }, 200);
}

// Event ketika focus pada search input
function onSearchInputFocus() {
    var input = document.getElementById('searchInput');
    var query = input.value.trim();
    
    if (query.length >= 2) {
        performSearch(query);
    }
}

// Event ketika blur dari search input
function onSearchInputBlur() {
    // Delay untuk memungkinkan klik pada item
    setTimeout(function() {
        hideSearchDropdown();
    }, 200);
}

// Clear search input
function clearSearchInput() {
    var input = document.getElementById('searchInput');
    input.value = '';
    document.getElementById('btnClearSearch').style.display = 'none';
    hideSearchDropdown();
    input.focus();
}

// Key groups untuk pencarian berdasarkan kategori
var searchKeyGroups = {
    router: ['router', 'mikrotik', 'server'],
    customer: ['client', 'pelanggan', 'customer'],
    odc: ['odc'],
    odp: ['odp'],
    olt: ['olt'],
    homepass: ['homepass'],
    tiang: ['tiang']
};

// Cek apakah query adalah key group
function getKeyGroupType(queryLower) {
    for (var type in searchKeyGroups) {
        var keywords = searchKeyGroups[type];
        for (var i = 0; i < keywords.length; i++) {
            if (queryLower === keywords[i]) {
                return type;
            }
        }
    }
    return null;
}

// Perform search
function performSearch(query) {
    var results = {
        routers: [],
        olts: [],
        odcs: [],
        odps: [],
        customers: [],
        tiang: [],
        homepass: []
    };
    
    var queryLower = query.toLowerCase().trim();
    
    // Cek apakah query adalah key group
    var keyGroupType = getKeyGroupType(queryLower);
    
    if (keyGroupType) {
        // Jika key group, tampilkan semua item dari kategori tersebut
        switch (keyGroupType) {
            case 'router':
                if (networkData.routers) {
                    results.routers = networkData.routers.slice(0, 5);
                }
                break;
            case 'olt':
                if (networkData.olts) {
                    results.olts = networkData.olts.slice(0, 5);
                }
                break;
            case 'odc':
                var odcSource = networkData.odcs_all || networkData.odcs || [];
                results.odcs = odcSource.slice(0, 5);
                break;
            case 'odp':
                var odpSource = networkData.odps_all || networkData.odps || [];
                results.odps = odpSource.slice(0, 5);
                break;
            case 'customer':
                if (networkData.customers) {
                    results.customers = networkData.customers.slice(0, 5);
                }
                break;
            case 'tiang':
                if (networkData.tiang) {
                    results.tiang = networkData.tiang.slice(0, 5);
                }
                break;
            case 'homepass':
                if (networkData.homepass) {
                    results.homepass = networkData.homepass.slice(0, 5);
                }
                break;
        }
    } else {
        // Pencarian normal berdasarkan nama/field
        
        // Search Routers
        if (networkData.routers) {
            networkData.routers.forEach(function(item) {
                if (matchSearch(item, queryLower, ['name', 'ip_address', 'address'])) {
                    results.routers.push(item);
                }
            });
            results.routers = results.routers.slice(0, 5);
        }
        
        // Search OLTs
        if (networkData.olts) {
            networkData.olts.forEach(function(item) {
                if (matchSearch(item, queryLower, ['name', 'brand', 'model', 'ip_address', 'address'])) {
                    results.olts.push(item);
                }
            });
            results.olts = results.olts.slice(0, 5);
        }
        
        // Search ODCs (dari odcs_all untuk individual slots)
        var odcSource = networkData.odcs_all || networkData.odcs || [];
        odcSource.forEach(function(item) {
            if (matchSearch(item, queryLower, ['name', 'box_id', 'address', 'description'])) {
                results.odcs.push(item);
            }
        });
        results.odcs = results.odcs.slice(0, 5);
        
        // Search ODPs (dari odps_all untuk individual slots)
        var odpSource = networkData.odps_all || networkData.odps || [];
        odpSource.forEach(function(item) {
            if (matchSearch(item, queryLower, ['name', 'box_id', 'address', 'description'])) {
                results.odps.push(item);
            }
        });
        results.odps = results.odps.slice(0, 5);
        
        // Search Customers
        if (networkData.customers) {
            networkData.customers.forEach(function(item) {
                if (matchSearch(item, queryLower, ['name', 'username', 'address', 'phone'])) {
                    results.customers.push(item);
                }
            });
            results.customers = results.customers.slice(0, 5);
        }
        
        // Search Tiang
        if (networkData.tiang) {
            networkData.tiang.forEach(function(item) {
                if (matchSearch(item, queryLower, ['name', 'address', 'tipe', 'pemilik'])) {
                    results.tiang.push(item);
                }
            });
            results.tiang = results.tiang.slice(0, 5);
        }
        
        // Search Homepass
        if (networkData.homepass) {
            networkData.homepass.forEach(function(item) {
                if (matchSearch(item, queryLower, ['name', 'address', 'phone', 'status'])) {
                    results.homepass.push(item);
                }
            });
            results.homepass = results.homepass.slice(0, 5);
        }
    }
    
    // Render results
    renderSearchResults(results, query);
}

// Match search helper
function matchSearch(item, queryLower, fields) {
    for (var i = 0; i < fields.length; i++) {
        var field = fields[i];
        if (item[field] && String(item[field]).toLowerCase().indexOf(queryLower) !== -1) {
            return true;
        }
    }
    return false;
}

// Render search results
function renderSearchResults(results, query) {
    var container = document.getElementById('searchDropdownContent');
    var html = '';
    searchResults = [];
    
    var totalResults = 0;
    
    // Routers
    if (results.routers.length > 0) {
        html += '<div class="search-category category-router"><i class="fa fa-server"></i> ROUTER</div>';
        results.routers.forEach(function(item) {
            searchResults.push({ type: 'router', data: item });
            html += buildSearchItem(item, 'router', query, item.name, item.ip_address || item.address || '-');
        });
        totalResults += results.routers.length;
    }
    
    // OLTs
    if (results.olts.length > 0) {
        html += '<div class="search-category category-olt"><i class="fa fa-sitemap"></i> OLT</div>';
        results.olts.forEach(function(item) {
            searchResults.push({ type: 'olt', data: item });
            var meta = item.brand ? item.brand + ' ' + (item.model || '') : (item.address || '-');
            html += buildSearchItem(item, 'olt', query, item.name, meta);
        });
        totalResults += results.olts.length;
    }
    
    // ODCs
    if (results.odcs.length > 0) {
        html += '<div class="search-category category-odc"><i class="fa fa-archive"></i> ODC</div>';
        results.odcs.forEach(function(item) {
            searchResults.push({ type: 'odc', data: item });
            var meta = item.slot_number ? 'Slot ' + item.slot_number + ' - ' : '';
            meta += item.address || '-';
            html += buildSearchItem(item, 'odc', query, item.name, meta);
        });
        totalResults += results.odcs.length;
    }
    
    // ODPs
    if (results.odps.length > 0) {
        html += '<div class="search-category category-odp"><i class="fa fa-circle-o"></i> ODP</div>';
        results.odps.forEach(function(item) {
            searchResults.push({ type: 'odp', data: item });
            var meta = item.slot_number ? 'Slot ' + item.slot_number + ' - ' : '';
            meta += item.address || '-';
            html += buildSearchItem(item, 'odp', query, item.name, meta);
        });
        totalResults += results.odps.length;
    }
    
    // Customers
    if (results.customers.length > 0) {
        html += '<div class="search-category category-customer"><i class="fa fa-user"></i> ' + LANG.search_cat_customer + '</div>';
        results.customers.forEach(function(item) {
            searchResults.push({ type: 'customer', data: item });
            html += buildSearchItemCustomer(item, query, item.name, item.address || item.username || '-');
        });
        totalResults += results.customers.length;
    }
    
    // Tiang
    if (results.tiang.length > 0) {
        html += '<div class="search-category category-tiang"><i class="fa fa-minus"></i> ' + LANG.search_cat_tiang + '</div>';
        results.tiang.forEach(function(item) {
            searchResults.push({ type: 'tiang', data: item });
            var meta = (item.tipe || '') + ' - ' + (item.address || '-');
            html += buildSearchItem(item, 'tiang', query, item.name, meta);
        });
        totalResults += results.tiang.length;
    }
    
    // Homepass
    if (results.homepass.length > 0) {
        html += '<div class="search-category category-homepass"><i class="fa fa-home"></i> ' + LANG.search_cat_homepass + '</div>';
        results.homepass.forEach(function(item) {
            searchResults.push({ type: 'homepass', data: item });
            var displayName = item.name || LANG.search_no_name;
            {literal}
            var hpSearchStatusMap = {'Prospek': LANG.homepass_status_prospek, 'Bermasalah': LANG.homepass_status_bermasalah, 'Tidak Minat': LANG.homepass_status_tidak_minat, 'Rumah Kosong': LANG.homepass_status_rumah_kosong, 'Sudah Langganan': LANG.homepass_status_sudah_langganan, 'Pending': LANG.homepass_status_pending};
            {/literal}
            var statusText = hpSearchStatusMap[item.status] || item.status || '';
            var meta = statusText + ' - ' + (item.address || '-');
            html += buildSearchItem(item, 'homepass', query, displayName, meta);
        });
        totalResults += results.homepass.length;
    }
    
    // Empty state
    if (totalResults === 0) {
        html = '<div class="search-empty">' +
                   '<i class="fa fa-search"></i>' +
                   '<div class="search-empty-text">' + LANG.search_no_results_1 + ' "<strong>' + escapeHtml(query) + '</strong>"</div>' +
               '</div>';
    }
    
    container.innerHTML = html;
    selectedSearchIndex = -1;
    showSearchDropdown();
}

// Build search item HTML
function buildSearchItem(item, type, query, name, meta) {
    var iconClass = 'icon-' + type;
    var iconFa = getIconForType(type);
    var highlightedName = highlightMatch(name, query);
    var index = searchResults.length - 1;
    
    return '<div class="search-item" data-index="' + index + '" onclick="onSearchItemClick(' + index + ')" onmouseenter="onSearchItemHover(' + index + ')">' +
               '<div class="search-item-icon ' + iconClass + '"><i class="fa ' + iconFa + '"></i></div>' +
               '<div class="search-item-content">' +
                   '<div class="search-item-name">' + highlightedName + '</div>' +
                   '<div class="search-item-meta">' + escapeHtml(meta) + '</div>' +
               '</div>' +
               '<div class="search-item-arrow"><i class="fa fa-chevron-right"></i></div>' +
           '</div>';
}

// Build search item khusus customer dengan status online/offline/isolir
function buildSearchItemCustomer(item, query, name, meta) {
    var highlightedName = highlightMatch(name, query);
    var index = searchResults.length - 1;
    
    // Cek status dari networkData (online_status dari database)
    var customer = networkData.customers ? networkData.customers.find(function(c) { return c.id == item.id; }) : null;
    var status = customer ? (customer.online_status || 'offline') : 'offline';
    
    /*
     * 4 Status:
     * 1. online    = Lingkaran hijau
     * 2. isolir    = Lingkaran pink-ungu + background pink transparan
     * 3. offline   = Lingkaran merah + background merah transparan
     * 4. off_isolir = Lingkaran merah + icon ban + background merah transparan
     */
    
    // Class tambahan untuk item
    var itemClass = 'search-item';
    if (status === 'offline' || status === 'off_isolir') {
        itemClass += ' search-item-offline';
    } else if (status === 'isolir') {
        itemClass += ' search-item-isolir';
    }
    
    // Badge status
    var statusBadge = '';
    if (status === 'online') {
        statusBadge = '<span class="search-status-badge status-online"><i class="fa fa-circle"></i></span>';
    } else if (status === 'isolir') {
        statusBadge = '<span class="search-status-badge status-isolir-connected"><i class="fa fa-circle"></i></span>';
    } else if (status === 'offline') {
        statusBadge = '<span class="search-status-badge status-offline"><i class="fa fa-circle"></i></span>';
    } else if (status === 'off_isolir') {
        statusBadge = '<span class="search-status-badge status-offline"><i class="fa fa-circle"></i></span>';
    }
    
    // Badge isolir (icon ban) untuk off_isolir
    var isolirBadge = '';
    if (status === 'off_isolir') {
        isolirBadge = '<span class="search-status-badge status-isolir" title="Isolir"><i class="fa fa-ban"></i></span>';
    }
    
    return '<div class="' + itemClass + '" data-index="' + index + '" onclick="onSearchItemClick(' + index + ')" onmouseenter="onSearchItemHover(' + index + ')">' +
               '<div class="search-item-icon icon-customer"><i class="fa fa-user"></i></div>' +
               '<div class="search-item-content">' +
                   '<div class="search-item-name">' + highlightedName + statusBadge + isolirBadge + '</div>' +
                   '<div class="search-item-meta">' + escapeHtml(meta) + '</div>' +
               '</div>' +
               '<div class="search-item-arrow"><i class="fa fa-chevron-right"></i></div>' +
           '</div>';
}

// Get icon for type
function getIconForType(type) {
    var icons = {
        router: 'fa-server',
        olt: 'fa-sitemap',
        odc: 'fa-archive',
        odp: 'fa-circle-o',
        customer: 'fa-user',
        tiang: 'fa-minus',
        homepass: 'fa-home'
    };
    return icons[type] || 'fa-map-marker';
}

// Highlight match
function highlightMatch(text, query) {
    if (!text || !query) return escapeHtml(text || '');
    
    var escaped = escapeHtml(text);
    var queryEscaped = escapeHtml(query);
    var regex = new RegExp('(' + queryEscaped.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
    return escaped.replace(regex, '<mark>$1</mark>');
}


// Show dropdown
function showSearchDropdown() {
    var dropdown = document.getElementById('searchDropdown');
    dropdown.classList.add('active');
    searchDropdownVisible = true;
}

// Hide dropdown
function hideSearchDropdown() {
    var dropdown = document.getElementById('searchDropdown');
    dropdown.classList.remove('active');
    searchDropdownVisible = false;
    selectedSearchIndex = -1;
}

// Navigate search results with keyboard
function navigateSearchResults(direction) {
    if (!searchDropdownVisible || searchResults.length === 0) return;
    
    selectedSearchIndex += direction;
    
    if (selectedSearchIndex < 0) {
        selectedSearchIndex = searchResults.length - 1;
    } else if (selectedSearchIndex >= searchResults.length) {
        selectedSearchIndex = 0;
    }
    
    updateSearchSelection();
}

// Update search selection UI
function updateSearchSelection() {
    var items = document.querySelectorAll('.search-item');
    items.forEach(function(item, index) {
        if (index === selectedSearchIndex) {
            item.classList.add('active');
            item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        } else {
            item.classList.remove('active');
        }
    });
}

// Select current search result
function selectCurrentSearchResult() {
    if (selectedSearchIndex >= 0 && selectedSearchIndex < searchResults.length) {
        onSearchItemClick(selectedSearchIndex);
    }
}

// Hover on search item
function onSearchItemHover(index) {
    selectedSearchIndex = index;
    updateSearchSelection();
}

// Click on search item
function onSearchItemClick(index) {
    if (index < 0 || index >= searchResults.length) return;
    
    var result = searchResults[index];
    var item = result.data;
    var type = result.type;
    
    hideSearchDropdown();
    
    // Get coordinates
    var coordinates = item.coordinates;
    if (!coordinates) {
        Swal.fire({
            icon: 'warning',
            title: LANG.alert_location_not_available,
            text: LANG.alert_device_no_coordinates,
            confirmButtonColor: '#3b82f6'
        });
        return;
    }
    
    // Parse coordinates
    var coords = coordinates.split(',').map(parseFloat);
    if (coords.length !== 2 || isNaN(coords[0]) || isNaN(coords[1])) {
        Swal.fire({
            icon: 'warning',
            title: LANG.alert_coordinates_invalid,
            text: LANG.alert_coordinates_invalid_text,
            confirmButtonColor: '#3b82f6'
        });
        return;
    }
    
    // Fly to location with animation
    flyToLocation(coords, type, item);
}

// Fly to location with smooth animation
function flyToLocation(coords, type, item) {
    // Tentukan zoom level berdasarkan tipe
    var zoomLevel = 18;
    if (type === 'router' || type === 'olt') {
        zoomLevel = 17;
    } else if (type === 'customer' || type === 'homepass') {
        zoomLevel = 19;
    }
    
    // Animate map ke lokasi
    map.flyTo(coords, zoomLevel, {
        duration: 1.2,
        easeLinearity: 0.25
    });
    
    // Setelah animasi selesai, buka popup
    setTimeout(function() {
        openMarkerPopup(coords, type, item);
    }, 1300);
}

// Open marker popup
function openMarkerPopup(coords, type, item) {
    var targetLayer = null;
    
    // Tentukan layer group berdasarkan tipe
    switch(type) {
        case 'router':
            targetLayer = markersGroup.routers;
            break;
        case 'olt':
            targetLayer = markersGroup.olts;
            break;
        case 'odc':
            targetLayer = markersGroup.odcs;
            break;
        case 'odp':
            targetLayer = markersGroup.odps;
            break;
        case 'customer':
            targetLayer = markersGroup.customers;
            break;
        case 'tiang':
            targetLayer = markersGroup.tiang;
            break;
        case 'homepass':
            targetLayer = markersGroup.homepass;
            break;
    }
    
    if (!targetLayer) return;
    
    // Cari marker yang cocok berdasarkan ID (prioritas) atau koordinat (fallback)
    var found = false;
    var itemId = item.id;
    
    targetLayer.eachLayer(function(layer) {
        if (found) return;
        
        // Prioritas 1: Cek berdasarkan ID dari customerData atau deviceData
        if (type === 'customer' && layer.options && layer.options.customerData) {
            if (layer.options.customerData.id == itemId) {
                layer.openPopup();
                found = true;
                return;
            }
        } else if (layer.deviceData && layer.deviceData.id == itemId) {
            layer.openPopup();
            found = true;
            return;
        }
        
        // Prioritas 2: Fallback ke koordinat dengan toleransi sangat ketat
        if (!found) {
            var markerCoords = layer.getLatLng();
            var tolerance = 0.000001;
            
            if (Math.abs(markerCoords.lat - coords[0]) < tolerance && 
                Math.abs(markerCoords.lng - coords[1]) < tolerance) {
                layer.openPopup();
                found = true;
            }
        }
    });
    
    // Jika tidak ditemukan di layer biasa, coba di cluster
    if (!found && targetLayer.getLayers) {
        targetLayer.getLayers().forEach(function(layer) {
            if (found) return;
            if (layer.getLatLng) {
                // Prioritas 1: Cek berdasarkan ID
                if (type === 'customer' && layer.options && layer.options.customerData) {
                    if (layer.options.customerData.id == itemId) {
                        layer.openPopup();
                        found = true;
                        return;
                    }
                } else if (layer.deviceData && layer.deviceData.id == itemId) {
                    layer.openPopup();
                    found = true;
                    return;
                }
                
                // Prioritas 2: Fallback ke koordinat
                if (!found) {
                    var markerCoords = layer.getLatLng();
                    var tolerance = 0.000001;
                    
                    if (Math.abs(markerCoords.lat - coords[0]) < tolerance && 
                        Math.abs(markerCoords.lng - coords[1]) < tolerance) {
                        layer.openPopup();
                        found = true;
                    }
                }
            }
        });
    }
}

// ==================== CEK STATUS ONLINE CUSTOMER ====================
// DEPRECATED: Batch check tidak diperlukan lagi
// Status customer sekarang dibaca langsung dari database (tbl_customer_online_status)
// yang di-update oleh cron_customer_monitor.php setiap 1 menit

/*
var customerOnlineCheckBatchSize = 100;
var customerOnlineCheckDelay = 800;
var customerOnlineCheckInterval = 240000;
var customerOnlineCheckIndex = 0;
var allCustomerIds = [];

function checkCustomersOnlineStatus() { ... }
function processNextCustomerBatch() { ... }
function updateCustomerMarkerColors(data) { ... }
*/

// Fungsi kosong untuk backward compatibility (jika ada yang masih memanggil)
function checkCustomersOnlineStatus() {
    console.log('Status customer sekarang dibaca dari database. Tidak perlu batch check.');
}

function processNextCustomerBatch() {
    // Deprecated
}

function updateCustomerMarkerColors(data) {
    // Deprecated - status sudah dari database saat load
}

// Fungsi untuk update counter online/offline/isolir
function updateOnlineOfflineCounter() {
    var countOnline = 0;
    var countOffline = 0;
    var countIsolir = 0;
    
    if (networkData.customers) {
        networkData.customers.forEach(function(customer) {
            var status = customer.online_status || 'offline';
            if (status === 'online') {
                countOnline++;
            } else if (status === 'offline') {
                countOffline++;
            } else if (status === 'isolir') {
                countIsolir++;
            } else if (status === 'off_isolir') {
                countOffline++; // Hitung sebagai offline saja
            }
        });
    }
    
    var onlineEl = document.getElementById('countOnline');
    var offlineEl = document.getElementById('countOffline');
    var isolirEl = document.getElementById('countIsolir');
    
    if (onlineEl) onlineEl.textContent = countOnline;
    if (offlineEl) offlineEl.textContent = countOffline;
    if (isolirEl) isolirEl.textContent = countIsolir;
}

// Fungsi untuk update warna kabel berdasarkan status customer (dari database)
function updateKabelCustomerStatus() {
    if (!networkData.kabel || !networkData.customers) return;
    
    networkData.kabel.forEach(function(kabel) {
        if (kabel.connectedCustomerId && kabel.polyline) {
            // Cari customer yang terhubung
            var customer = networkData.customers.find(function(c) {
                return c.id == kabel.connectedCustomerId;
            });
            
            if (!customer) return;
            
            var status = customer.online_status || 'offline';
            var polylineElement = kabel.polyline.getElement();
            
            if (status === 'offline' || status === 'off_isolir') {
                // Customer offline - kabel merah berkedip
                kabel.polyline.setStyle({
                    color: '#ef4444',
                    weight: 5
                });
                if (polylineElement) {
                    polylineElement.classList.add('kabel-offline-blink');
                }
            } else if (status === 'isolir') {
                // Customer isolir - kabel pink-ungu berkedip
                kabel.polyline.setStyle({
                    color: '#a855f7',
                    weight: 5
                });
                if (polylineElement) {
                    polylineElement.classList.remove('kabel-offline-blink');
                    polylineElement.classList.add('kabel-isolir-blink');
                }
            } else {
                // Customer online - kabel normal
                kabel.polyline.setStyle({
                    color: kabel.cable_color || '#78716c',
                    weight: 4
                });
                if (polylineElement) {
                    polylineElement.classList.remove('kabel-offline-blink');
                    polylineElement.classList.remove('kabel-isolir-blink');
                }
            }
        }
    });
}
// Fungsi untuk re-apply class blink pada kabel offline dan isolir setelah show/zoom
function reapplyKabelOfflineBlink() {
    if (!networkData.kabel || !networkData.customers) return;
    
    networkData.kabel.forEach(function(kabel) {
        if (kabel.connectedCustomerId && kabel.polyline) {
            // Cari customer yang terhubung
            var customer = networkData.customers.find(function(c) {
                return c.id == kabel.connectedCustomerId;
            });
            
            if (!customer) return;
            
            var status = customer.online_status || 'offline';
            
            if (status === 'offline' || status === 'off_isolir') {
                // Customer offline - re-apply style dan class
                kabel.polyline.setStyle({
                    color: '#ef4444',
                    weight: 5
                });
                
                // Tunggu element ready lalu apply class
                setTimeout(function() {
                    var polylineElement = kabel.polyline.getElement();
                    if (polylineElement) {
                        polylineElement.classList.remove('kabel-isolir-blink');
                        polylineElement.classList.add('kabel-offline-blink');
                    }
                }, 50);
            } else if (status === 'isolir') {
                // Customer isolir - re-apply style dan class
                kabel.polyline.setStyle({
                    color: '#a855f7',
                    weight: 5
                });
                
                // Tunggu element ready lalu apply class
                setTimeout(function() {
                    var polylineElement = kabel.polyline.getElement();
                    if (polylineElement) {
                        polylineElement.classList.remove('kabel-offline-blink');
                        polylineElement.classList.add('kabel-isolir-blink');
                    }
                }, 50);
            }
        }
    });
}

// Fungsi untuk update animasi flow kabel berdasarkan zoom
function updateKabelFlowAnimation(enableFlow) {
    if (!networkData.kabel) return;
    
    networkData.kabel.forEach(function(kabel) {
        if (!kabel.polyline) return;
        
        var polylineElement = kabel.polyline.getElement();
        if (!polylineElement) return;
        
        // Cek apakah kabel ini terhubung ke customer
        if (kabel.connectedCustomerId && networkData.customers) {
            var customer = networkData.customers.find(function(c) {
                return c.id == kabel.connectedCustomerId;
            });
            
            if (customer) {
                var status = customer.online_status || 'offline';
                
                // Kabel ke customer offline/isolir = berkedip, tanpa flow
                if (status === 'offline' || status === 'off_isolir' || status === 'isolir') {
                    polylineElement.classList.remove('kabel-flow-animation');
                    return;
                }
            }
        }
        
        // Semua kabel lainnya dapat animasi flow
        if (enableFlow) {
            polylineElement.classList.add('kabel-flow-animation');
        } else {
            polylineElement.classList.remove('kabel-flow-animation');
        }
    });
}

// ==================== REALTIME MONITORING (SPEED & PING) ====================

var realtimeMonitoringInterval = null;
var realtimeMonitoringCustomer = null;
var realtimeMonitoringDelay = 3000; // 3 detik

function startRealtimeMonitoring(customer) {
    // Stop monitoring sebelumnya jika ada
    stopRealtimeMonitoring();
    
    realtimeMonitoringCustomer = customer;
    
    // Request pertama langsung
    fetchRealtimeData(customer);
    
    // Set interval untuk request berikutnya
    realtimeMonitoringInterval = setInterval(function() {
        fetchRealtimeData(customer);
    }, realtimeMonitoringDelay);
}

function stopRealtimeMonitoring() {
    if (realtimeMonitoringInterval) {
        clearInterval(realtimeMonitoringInterval);
        realtimeMonitoringInterval = null;
    }
    realtimeMonitoringCustomer = null;
}

function fetchRealtimeData(customer) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '?_route=plugin/network_mapping/get-customer-realtime', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.status === 'success') {
                    updateRealtimeDisplay(customer, response);
                }
            } catch (e) {
                console.error('Error parsing realtime response:', e);
            }
        }
    };
    xhr.send('customer_id=' + customer.id);
}

function updateRealtimeDisplay(customer, data) {
    // Update nilai di customer data
    customer.realtimeUpload = data.upload || '0 bps';
    customer.realtimeDownload = data.download || '0 bps';
    customer.realtimePing = data.ping || 'Timeout';
    customer.realtimeUptime = data.uptime || '-';
    
    // Update IP hanya jika dari MikroTik ada isinya (untuk customer yang pppoe_ip kosong di database)
    if (data.ip_address && data.ip_address !== '') {
        customer.realtimeIp = data.ip_address;
    }
    
    // Update tampilan di popup jika masih terbuka
    var uploadEl = document.getElementById('realtime-upload-' + customer.id);
    var downloadEl = document.getElementById('realtime-download-' + customer.id);
    var pingEl = document.getElementById('realtime-ping-' + customer.id);
    var uptimeEl = document.getElementById('realtime-uptime-' + customer.id);
    var ipEl = document.getElementById('realtime-ip-' + customer.id);
    
    if (uploadEl) uploadEl.textContent = customer.realtimeUpload;
    if (downloadEl) downloadEl.textContent = customer.realtimeDownload;
    if (pingEl) {
        pingEl.textContent = customer.realtimePing;
        
        // Update warna ping secara dinamis
        var pingClass = getPingColorClass(customer.realtimePing);
        pingEl.className = pingClass;
        
        // Update warna icon ping juga
        var pingIcon = pingEl.parentElement.querySelector('.fa-exchange');
        if (pingIcon) {
            pingIcon.className = 'fa fa-exchange ' + pingClass;
        }
    }
    if (uptimeEl) uptimeEl.textContent = customer.realtimeUptime;
    if (ipEl && customer.realtimeIp) ipEl.textContent = customer.realtimeIp;
}

// Fungsi untuk update counter router aktif/nonaktif
function updateRouterCounter() {
    var countAktif = 0;
    var countNonaktif = 0;
    
    if (networkData.routers) {
        networkData.routers.forEach(function(router) {
            if (router.enabled == 1) {
                countAktif++;
            } else {
                countNonaktif++;
            }
        });
    }
    
    var aktifEl = document.getElementById('countRouterAktif');
    var nonaktifEl = document.getElementById('countRouterNonaktif');
    
    if (aktifEl) aktifEl.textContent = countAktif;
    if (nonaktifEl) nonaktifEl.textContent = countNonaktif;
    
    // Update juga counter OLT
    updateOltCounter();
}

// Fungsi untuk update counter OLT berdasarkan status
function updateOltCounter() {
    var countActive = 0;
    var countMaintenance = 0;
    var countInactive = 0;
    
    if (networkData.olts) {
        networkData.olts.forEach(function(olt) {
            if (olt.status === 'Active') {
                countActive++;
            } else if (olt.status === 'Maintenance') {
                countMaintenance++;
            } else {
                countInactive++;
            }
        });
    }
    
    var activeEl = document.getElementById('countOltActive');
    var maintenanceEl = document.getElementById('countOltMaintenance');
    var inactiveEl = document.getElementById('countOltInactive');
    
    if (activeEl) activeEl.textContent = countActive;
    if (maintenanceEl) maintenanceEl.textContent = countMaintenance;
    if (inactiveEl) inactiveEl.textContent = countInactive;
    
    // Update juga counter ODC
    updateOdcCounter();
}

// Fungsi untuk update counter ODC berdasarkan status (hitung per slot)
function updateOdcCounter() {
    var countActive = 0;
    var countMaintenance = 0;
    var countInactive = 0;
    
    // Gunakan odcs_all untuk hitung semua slot, bukan per box
    if (networkData.odcs_all) {
        networkData.odcs_all.forEach(function(odc) {
            var status = odc.status || '';
            if (status === 'Active') {
                countActive++;
            } else if (status === 'Maintenance') {
                countMaintenance++;
            } else if (status === 'Inactive') {
                countInactive++;
            }
        });
    }
    
    var activeEl = document.getElementById('countOdcActive');
    var maintenanceEl = document.getElementById('countOdcMaintenance');
    var inactiveEl = document.getElementById('countOdcInactive');
    
    if (activeEl) activeEl.textContent = countActive;
    if (maintenanceEl) maintenanceEl.textContent = countMaintenance;
    if (inactiveEl) inactiveEl.textContent = countInactive;
    
    // Update juga counter ODP
    updateOdpCounter();
}

// Fungsi untuk update counter ODP berdasarkan status (hitung per slot)
function updateOdpCounter() {
    var countActive = 0;
    var countMaintenance = 0;
    var countInactive = 0;
    
    // Gunakan odps_all untuk hitung semua slot, bukan per box
    if (networkData.odps_all) {
        networkData.odps_all.forEach(function(odp) {
            var status = odp.status || '';
            if (status === 'Active') {
                countActive++;
            } else if (status === 'Maintenance') {
                countMaintenance++;
            } else if (status === 'Inactive') {
                countInactive++;
            }
        });
    }
    
    var activeEl = document.getElementById('countOdpActive');
    var maintenanceEl = document.getElementById('countOdpMaintenance');
    var inactiveEl = document.getElementById('countOdpInactive');
    
    if (activeEl) activeEl.textContent = countActive;
    if (maintenanceEl) maintenanceEl.textContent = countMaintenance;
    if (inactiveEl) inactiveEl.textContent = countInactive;
    
    // Update juga counter Tiang
    updateTiangCounter();
}

// Fungsi untuk update counter Tiang berdasarkan status
function updateTiangCounter() {
    var countBagus = 0;
    var countPerbaikan = 0;
    var countRusak = 0;
    
    if (networkData.tiang) {
        networkData.tiang.forEach(function(tiang) {
            var status = tiang.status || '';
            if (status === 'Aktif') {
                countBagus++;
            } else if (status === 'Perbaikan') {
                countPerbaikan++;
            } else if (status === 'Rusak') {
                countRusak++;
            }
        });
    }
    
    var bagusEl = document.getElementById('countTiangBagus');
    var perbaikanEl = document.getElementById('countTiangPerbaikan');
    var rusakEl = document.getElementById('countTiangRusak');
    
    if (bagusEl) bagusEl.textContent = countBagus;
    if (perbaikanEl) perbaikanEl.textContent = countPerbaikan;
    if (rusakEl) rusakEl.textContent = countRusak;
    
    // Update juga counter Homepass
    updateHomepassCounter();
}

// Fungsi untuk update counter Homepass berdasarkan status
function updateHomepassCounter() {
    var countProspek = 0;
    var countPending = 0;
    var countTidakMinat = 0;
    var countTidakMampu = 0;
    var countRumahKosong = 0;
    var countLangganan = 0;
    
    if (networkData.homepass) {
        networkData.homepass.forEach(function(hp) {
            var status = hp.status || '';
            if (status === 'Prospek') {
                countProspek++;
            } else if (status === 'Pending') {
                countPending++;
            } else if (status === 'Tidak Minat') {
                countTidakMinat++;
            } else if (status === 'Tidak Mampu') {
                countTidakMampu++;
            } else if (status === 'Rumah Kosong') {
                countRumahKosong++;
            } else if (status === 'Sudah Langganan') {
                countLangganan++;
            }
        });
    }
    
    var prospekEl = document.getElementById('countHpProspek');
    var pendingEl = document.getElementById('countHpPending');
    var tidakMinatEl = document.getElementById('countHpTidakMinat');
    var tidakMampuEl = document.getElementById('countHpTidakMampu');
    var rumahKosongEl = document.getElementById('countHpRumahKosong');
    var langgananEl = document.getElementById('countHpLangganan');
    
    if (prospekEl) prospekEl.textContent = countProspek;
    if (pendingEl) pendingEl.textContent = countPending;
    if (tidakMinatEl) tidakMinatEl.textContent = countTidakMinat;
    if (tidakMampuEl) tidakMampuEl.textContent = countTidakMampu;
    if (rumahKosongEl) rumahKosongEl.textContent = countRumahKosong;
    if (langgananEl) langgananEl.textContent = countLangganan;
}

// ==================== FUNGSI FOTO TIANG ====================

// Helper untuk mendapatkan base URL tanpa routing
function getCleanBaseUrl() {
    var cleanUrl = baseUrl.replace('?_route=', '');
    cleanUrl = cleanUrl.replace(/\/$/, '') + '/';
    return cleanUrl;
}

// Preview foto saat upload di form tambah
function previewFotoTiang(input) {
    var placeholder = document.getElementById('tiangFotoPlaceholder');
    var preview = document.getElementById('tiangFotoPreview');
    var previewImg = document.getElementById('tiangFotoPreviewImg');
    
    if (input.files && input.files[0]) {
        // Validasi ukuran
        var fileSize = input.files[0].size;
        var maxSize = 5 * 1024 * 1024; // 5MB
        if (fileSize > maxSize) {
            Swal.fire(LANG.alert_error, LANG.alert_photo_max_5mb, 'error');
            input.value = '';
            return;
        }
        
        // Validasi tipe
        var fileType = input.files[0].type;
        var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(fileType)) {
            Swal.fire(LANG.alert_error, LANG.alert_photo_format_invalid, 'error');
            input.value = '';
            return;
        }
        
        var reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            placeholder.style.display = 'none';
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Hapus preview foto di form tambah
function hapusFotoTiangPreview() {
    var input = document.getElementById('tiangFoto');
    var placeholder = document.getElementById('tiangFotoPlaceholder');
    var preview = document.getElementById('tiangFotoPreview');
    var previewImg = document.getElementById('tiangFotoPreviewImg');
    
    input.value = '';
    previewImg.src = '';
    preview.style.display = 'none';
    placeholder.style.display = 'flex';
}

// Preview foto saat upload di form edit
function previewFotoTiangEdit(input) {
    var placeholder = document.getElementById('editTiangFotoPlaceholder');
    var preview = document.getElementById('editTiangFotoPreview');
    var previewImg = document.getElementById('editTiangFotoPreviewImg');
    var hapusFotoInput = document.getElementById('editTiangHapusFoto');
    
    if (input.files && input.files[0]) {
        // Validasi ukuran
        var fileSize = input.files[0].size;
        var maxSize = 5 * 1024 * 1024; // 5MB
        if (fileSize > maxSize) {
            Swal.fire(LANG.alert_error, LANG.alert_photo_max_5mb, 'error');
            input.value = '';
            return;
        }
        
        // Validasi tipe
        var fileType = input.files[0].type;
        var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(fileType)) {
            Swal.fire(LANG.alert_error, LANG.alert_photo_format_invalid, 'error');
            input.value = '';
            return;
        }
        
        var reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            placeholder.style.display = 'none';
            preview.style.display = 'block';
            hapusFotoInput.value = '0'; // Reset hapus flag karena ada foto baru
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Hapus preview foto di form edit
function hapusFotoTiangEditPreview() {
    var input = document.getElementById('editTiangFoto');
    var placeholder = document.getElementById('editTiangFotoPlaceholder');
    var preview = document.getElementById('editTiangFotoPreview');
    var previewImg = document.getElementById('editTiangFotoPreviewImg');
    var hapusFotoInput = document.getElementById('editTiangHapusFoto');
    
    input.value = '';
    previewImg.src = '';
    preview.style.display = 'none';
    placeholder.style.display = 'flex';
    hapusFotoInput.value = '1'; // Set flag untuk hapus foto di server
}

// Lihat foto fullscreen (dari popup marker)
// type: 'tiang', 'odc', 'odp', 'sambungan' untuk warna header berbeda
function lihatFotoFullscreen(fotoUrl, title, type, customerStatus) {
    // Tentukan class header berdasarkan type
    var headerClass = 'foto-fullscreen-header';
    if (type === 'odc') {
        headerClass += ' foto-fullscreen-header-odc';
    } else if (type === 'odp') {
        headerClass += ' foto-fullscreen-header-odp';
    } else if (type === 'homepass') {
        headerClass += ' foto-fullscreen-header-homepass';
    } else if (type === 'sambungan') {
        headerClass += ' foto-fullscreen-header-sambungan';
    } else if (type === 'customer') {
        // Warna header sesuai status customer
        if (customerStatus === 'online') {
            headerClass += ' foto-fullscreen-header-customer-online';
        } else if (customerStatus === 'isolir') {
            headerClass += ' foto-fullscreen-header-customer-isolir';
        } else {
            // offline atau off_isolir
            headerClass += ' foto-fullscreen-header-customer-offline';
        }
    }
    // Default (tiang) sudah warna coklat dari CSS dasar
    
    // Buat modal custom untuk foto fullscreen
    var modalHtml = 
        '<div class="foto-fullscreen-overlay" id="fotoFullscreenOverlay" onclick="tutupFotoFullscreen(event)">' +
            '<div class="foto-fullscreen-container" onclick="event.stopPropagation()">' +
                '<div class="' + headerClass + '">' +
                    '<div class="foto-fullscreen-title"><i class="fa fa-image"></i> ' + title + '</div>' +
                    '<button class="foto-fullscreen-close" onclick="tutupFotoFullscreen(event)">&times;</button>' +
                '</div>' +
                '<div class="foto-fullscreen-body">' +
                    '<img src="' + fotoUrl + '" alt="' + title + '" class="foto-fullscreen-img">' +
                '</div>' +
            '</div>' +
        '</div>';
    
    // Hapus modal lama jika ada
    var existingModal = document.getElementById('fotoFullscreenOverlay');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Tambahkan modal ke body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Tampilkan dengan animasi
    setTimeout(function() {
        document.getElementById('fotoFullscreenOverlay').classList.add('active');
    }, 10);
}
// ==================== FUNGSI FOTO ODC ====================

// Preview foto saat upload di form edit ODC
function previewFotoOdcEdit(input) {
    var placeholder = document.getElementById('editOdcFotoPlaceholder');
    var preview = document.getElementById('editOdcFotoPreview');
    var previewImg = document.getElementById('editOdcFotoPreviewImg');
    var hapusFotoInput = document.getElementById('editOdcHapusFoto');
    
    if (input.files && input.files[0]) {
        // Validasi ukuran
        var fileSize = input.files[0].size;
        var maxSize = 5 * 1024 * 1024; // 5MB
        if (fileSize > maxSize) {
            Swal.fire(LANG.alert_error, LANG.alert_photo_max_5mb, 'error');
            input.value = '';
            return;
        }
        
        // Validasi tipe
        var fileType = input.files[0].type;
        var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(fileType)) {
            Swal.fire(LANG.alert_error, LANG.alert_photo_format_invalid, 'error');
            input.value = '';
            return;
        }
        
        var reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            placeholder.style.display = 'none';
            preview.style.display = 'block';
            hapusFotoInput.value = '0';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Hapus preview foto di form edit ODC
function hapusFotoOdcEditPreview() {
    var input = document.getElementById('editOdcFoto');
    var placeholder = document.getElementById('editOdcFotoPlaceholder');
    var preview = document.getElementById('editOdcFotoPreview');
    var previewImg = document.getElementById('editOdcFotoPreviewImg');
    var hapusFotoInput = document.getElementById('editOdcHapusFoto');
    
    input.value = '';
    previewImg.src = '';
    preview.style.display = 'none';
    placeholder.style.display = 'flex';
    hapusFotoInput.value = '1';
}
// Preview foto saat upload di form tambah ODC
function previewFotoOdcAdd(input) {
    var placeholder = document.getElementById('addOdcFotoPlaceholder');
    var preview = document.getElementById('addOdcFotoPreview');
    var previewImg = document.getElementById('addOdcFotoPreviewImg');
    
    if (input.files && input.files[0]) {
        // Validasi ukuran
        var fileSize = input.files[0].size;
        var maxSize = 5 * 1024 * 1024; // 5MB
        if (fileSize > maxSize) {
            Swal.fire(LANG.alert_error, LANG.alert_photo_max_5mb, 'error');
            input.value = '';
            return;
        }
        
        // Validasi tipe
        var fileType = input.files[0].type;
        var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(fileType)) {
            Swal.fire(LANG.alert_error, LANG.alert_photo_format_invalid, 'error');
            input.value = '';
            return;
        }
        
        var reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            placeholder.style.display = 'none';
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Hapus preview foto di form tambah ODC
function hapusFotoOdcAddPreview() {
    var input = document.getElementById('addOdcFoto');
    var placeholder = document.getElementById('addOdcFotoPlaceholder');
    var preview = document.getElementById('addOdcFotoPreview');
    var previewImg = document.getElementById('addOdcFotoPreviewImg');
    
    input.value = '';
    previewImg.src = '';
    preview.style.display = 'none';
    placeholder.style.display = 'flex';
}
// ==================== FUNGSI FOTO ODP ====================

// Preview foto saat upload di form tambah ODP
function previewFotoOdpAdd(input) {
    var placeholder = document.getElementById('addOdpFotoPlaceholder');
    var preview = document.getElementById('addOdpFotoPreview');
    var previewImg = document.getElementById('addOdpFotoPreviewImg');
    
    if (input.files && input.files[0]) {
        // Validasi ukuran
        var fileSize = input.files[0].size;
        var maxSize = 5 * 1024 * 1024; // 5MB
        if (fileSize > maxSize) {
            Swal.fire(LANG.alert_error, LANG.alert_photo_max_5mb, 'error');
            input.value = '';
            return;
        }
        
        // Validasi tipe
        var fileType = input.files[0].type;
        var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(fileType)) {
            Swal.fire(LANG.alert_error, LANG.alert_photo_format_invalid, 'error');
            input.value = '';
            return;
        }
        
        var reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            placeholder.style.display = 'none';
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Hapus preview foto di form tambah ODP
function hapusFotoOdpAddPreview() {
    var input = document.getElementById('addOdpFoto');
    var placeholder = document.getElementById('addOdpFotoPlaceholder');
    var preview = document.getElementById('addOdpFotoPreview');
    var previewImg = document.getElementById('addOdpFotoPreviewImg');
    
    input.value = '';
    previewImg.src = '';
    preview.style.display = 'none';
    placeholder.style.display = 'flex';
}

// Preview foto saat upload di form edit ODP
function previewFotoOdpEdit(input) {
    var placeholder = document.getElementById('editOdpFotoPlaceholder');
    var preview = document.getElementById('editOdpFotoPreview');
    var previewImg = document.getElementById('editOdpFotoPreviewImg');
    
    if (input.files && input.files[0]) {
        // Validasi ukuran
        var fileSize = input.files[0].size;
        var maxSize = 5 * 1024 * 1024; // 5MB
        if (fileSize > maxSize) {
            Swal.fire(LANG.alert_error, LANG.alert_photo_max_5mb, 'error');
            input.value = '';
            return;
        }
        
        // Validasi tipe
        var fileType = input.files[0].type;
        var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(fileType)) {
            Swal.fire(LANG.alert_error, LANG.alert_photo_format_invalid, 'error');
            input.value = '';
            return;
        }
        
        var reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            placeholder.style.display = 'none';
            preview.style.display = 'block';
            // Reset flag hapus karena ada foto baru
            document.getElementById('editOdpHapusFoto').value = '0';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Hapus preview foto di form edit ODP
function hapusFotoOdpEditPreview() {
    var input = document.getElementById('editOdpFoto');
    var placeholder = document.getElementById('editOdpFotoPlaceholder');
    var preview = document.getElementById('editOdpFotoPreview');
    var previewImg = document.getElementById('editOdpFotoPreviewImg');
    
    input.value = '';
    previewImg.src = '';
    preview.style.display = 'none';
    placeholder.style.display = 'flex';
    
    // Set flag untuk hapus foto di server
    document.getElementById('editOdpHapusFoto').value = '1';
}
// ==================== FUNGSI FOTO HOMEPASS ====================

// Preview foto saat upload di form tambah Homepass
function previewFotoHomepassAdd(input) {
    var placeholder = document.getElementById('addHomepassFotoPlaceholder');
    var preview = document.getElementById('addHomepassFotoPreview');
    var previewImg = document.getElementById('addHomepassFotoPreviewImg');
    
    if (input.files && input.files[0]) {
        // Validasi ukuran
        var fileSize = input.files[0].size;
        var maxSize = 5 * 1024 * 1024; // 5MB
        if (fileSize > maxSize) {
            Swal.fire('Error', LANG.alert_photo_size_invalid, 'error');
            input.value = '';
            return;
        }
        
        // Validasi tipe
        var fileType = input.files[0].type;
        var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(fileType)) {
            Swal.fire('Error', LANG.alert_photo_format_invalid, 'error');
            input.value = '';
            return;
        }
        
        var reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            placeholder.style.display = 'none';
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Hapus preview foto di form tambah Homepass
function hapusFotoHomepassAddPreview() {
    var input = document.getElementById('addHomepassFoto');
    var placeholder = document.getElementById('addHomepassFotoPlaceholder');
    var preview = document.getElementById('addHomepassFotoPreview');
    var previewImg = document.getElementById('addHomepassFotoPreviewImg');
    
    input.value = '';
    previewImg.src = '';
    preview.style.display = 'none';
    placeholder.style.display = 'flex';
}

// Preview foto saat upload di form edit Homepass
function previewFotoHomepassEdit(input) {
    var placeholder = document.getElementById('editHomepassFotoPlaceholder');
    var preview = document.getElementById('editHomepassFotoPreview');
    var previewImg = document.getElementById('editHomepassFotoPreviewImg');
    
    if (input.files && input.files[0]) {
        // Validasi ukuran
        var fileSize = input.files[0].size;
        var maxSize = 5 * 1024 * 1024; // 5MB
        if (fileSize > maxSize) {
            Swal.fire('Error', LANG.alert_photo_size_invalid, 'error');
            input.value = '';
            return;
        }
        
        // Validasi tipe
        var fileType = input.files[0].type;
        var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(fileType)) {
            Swal.fire('Error', LANG.alert_photo_format_invalid, 'error');
            input.value = '';
            return;
        }
        
        var reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            placeholder.style.display = 'none';
            preview.style.display = 'block';
            // Reset flag hapus karena ada foto baru
            document.getElementById('editHomepassHapusFoto').value = '0';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Hapus preview foto di form edit Homepass
function hapusFotoHomepassEditPreview() {
    var input = document.getElementById('editHomepassFoto');
    var placeholder = document.getElementById('editHomepassFotoPlaceholder');
    var preview = document.getElementById('editHomepassFotoPreview');
    var previewImg = document.getElementById('editHomepassFotoPreviewImg');
    
    input.value = '';
    previewImg.src = '';
    preview.style.display = 'none';
    placeholder.style.display = 'flex';
    
    // Set flag untuk hapus foto di server
    document.getElementById('editHomepassHapusFoto').value = '1';
}

// Tutup foto fullscreen
function tutupFotoFullscreen(event) {
    if (event) {
        event.stopPropagation();
    }
    var modal = document.getElementById('fotoFullscreenOverlay');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(function() {
            modal.remove();
        }, 300);
    }
}

// ==================== FUNGSI FOTO SAMBUNGAN KABEL ====================

// Tampilkan popup foto sambungan
function tampilkanPopupFotoSambungan(data) {
    // Hapus popup lama jika ada
    var existingPopup = document.getElementById('popupFotoSambunganOverlay');
    if (existingPopup) {
        existingPopup.remove();
    }
    
    var fotoHtml = '';
    if (data.foto && data.foto_url) {
        // Ada foto - tampilkan preview dengan tombol swap dan hapus
        fotoHtml = 
            '<div class="foto-sambungan-preview" id="sambunganFotoPreview">' +
                '<img src="' + data.foto_url + '" alt="Foto Sambungan" id="sambunganFotoPreviewImg" onclick="lihatFotoFullscreen(\'' + data.foto_url + '\', \'Sambungan #' + data.sambungan_index + ' - ' + data.kabel_name + '\', \'sambungan\')">' +
                '<div class="foto-sambungan-actions">' +
                    '<button type="button" class="btn-foto-sambungan-change" onclick="triggerUploadFotoSambungan()" title="' + LANG.btn_change_photo + '">' +
                        '<i class="fa fa-refresh"></i>' +
                    '</button>' +
                    '<button type="button" class="btn-foto-sambungan-remove" onclick="hapusFotoSambungan(' + data.kabel_id + ', ' + data.sambungan_index + ')" title="' + LANG.btn_delete_photo + '">' +
                        '<i class="fa fa-trash"></i>' +
                    '</button>' +
                '</div>' +
            '</div>';
    } else {
        // Tidak ada foto - tampilkan placeholder upload
        fotoHtml = 
            '<div class="foto-sambungan-placeholder" id="sambunganFotoPlaceholder" onclick="triggerUploadFotoSambungan()">' +
                '<i class="fa fa-camera"></i>' +
                '<span>' + LANG.click_to_upload_photo + '</span>' +
            '</div>';
    }
    
    var popupHtml = 
        '<div class="popup-foto-sambungan-overlay" id="popupFotoSambunganOverlay" onclick="tutupPopupFotoSambungan(event)">' +
            '<div class="popup-foto-sambungan-container" onclick="event.stopPropagation()">' +
                '<div class="popup-foto-sambungan-header">' +
                    '<div class="popup-foto-sambungan-title">' +
                        '<i class="fa fa-link"></i> ' + LANG.kabel_connection_number + data.sambungan_index +
                    '</div>' +
                    '<button class="popup-foto-sambungan-close" onclick="tutupPopupFotoSambungan(event)">&times;</button>' +
                '</div>' +
                '<div class="popup-foto-sambungan-body">' +
                    '<div class="popup-foto-sambungan-section">' +
                        '<div class="popup-foto-sambungan-label">' +
                            '<i class="fa fa-camera"></i> ' + LANG.photo_connection_location + ' <small class="text-muted">(Max 5MB)</small>' +
                        '</div>' +
                        '<input type="file" id="inputFotoSambungan" accept="image/jpeg,image/jpg,image/png,image/webp" style="display: none;" onchange="uploadFotoSambungan(' + data.kabel_id + ', ' + data.sambungan_index + ', this)">' +
                        '<input type="hidden" id="currentSambunganKabelId" value="' + data.kabel_id + '">' +
                        '<input type="hidden" id="currentSambunganIndex" value="' + data.sambungan_index + '">' +
                        '<div class="foto-sambungan-wrapper">' +
                            fotoHtml +
                        '</div>' +
                    '</div>' +
                    '<div class="popup-foto-sambungan-info">' +
                        '<div class="popup-foto-sambungan-info-row">' +
                            '<i class="fa fa-minus"></i> <span>' + LANG.kabel + ': <strong>' + data.kabel_name + '</strong></span>' +
                        '</div>' +
                        '<div class="popup-foto-sambungan-info-row">' +
                            '<i class="fa fa-arrows-h"></i> <span>' + LANG.cable_distance + ': <strong>' + data.jarak + ' meter</strong></span>' +
                        '</div>' +
                        '<div class="popup-foto-sambungan-info-row">' +
                            '<i class="fa fa-exchange"></i> <span>' + LANG.point_label + ': ' + LANG.js_from + ' <strong>' + data.device_1_name + '</strong> ' + LANG.to_label + ' <strong>' + data.device_2_name + '</strong></span>' +
                        '</div>' +
                        '<div class="popup-foto-sambungan-info-row">' +
                            '<i class="fa fa-map-marker"></i> <span>' + LANG.form_coordinates + ': <strong>' + data.coordinates + '</strong></span>' +
                        '</div>' +
                        (data.keterangan ? '<div class="popup-foto-sambungan-info-row"><i class="fa fa-comment"></i> <span>' + data.keterangan + '</span></div>' : '') +
                    '</div>' +
                    '<div class="popup-foto-sambungan-rute">' +
                        '<a href="https://www.google.com/maps/dir//' + data.coordinates + '" target="_blank" class="btn-foto-sambungan-rute">' +
                            '<i class="fa fa-location-arrow"></i> ' + LANG.route_to_location + '' +
                        '</a>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
    
    // Tambahkan ke body
    document.body.insertAdjacentHTML('beforeend', popupHtml);
    
    // Tampilkan dengan animasi
    setTimeout(function() {
        document.getElementById('popupFotoSambunganOverlay').classList.add('active');
    }, 10);
}

// Tutup popup foto sambungan
function tutupPopupFotoSambungan(event) {
    if (event) {
        event.stopPropagation();
    }
    var popup = document.getElementById('popupFotoSambunganOverlay');
    if (popup) {
        popup.classList.remove('active');
        setTimeout(function() {
            popup.remove();
        }, 300);
    }
}

// Trigger input file untuk upload foto
function triggerUploadFotoSambungan() {
    document.getElementById('inputFotoSambungan').click();
}

// Upload foto sambungan
function uploadFotoSambungan(kabelId, sambunganIndex, input) {
    if (!input.files || !input.files[0]) {
        return;
    }
    
    var file = input.files[0];
    
    // Validasi ukuran
    var maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        Swal.fire('Error', LANG.alert_photo_size_invalid, 'error');
        input.value = '';
        return;
    }
    
    // Validasi tipe
    var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        Swal.fire('Error', LANG.alert_photo_format_invalid, 'error');
        input.value = '';
        return;
    }
    
    // Tampilkan loading
    Swal.fire({
        title: LANG.uploading,
        text: LANG.wait_a_moment,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: function() {
            Swal.showLoading();
        }
    });
    
    // Buat FormData
    var formData = new FormData();
    formData.append('foto_sambungan', file);
    formData.append('kabel_id', kabelId);
    formData.append('sambungan_index', sambunganIndex);
    
    // Upload via AJAX
    fetch(baseUrl + 'plugin/network_mapping/upload-foto-sambungan', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        Swal.close();
        
        if (data.status === 'success') {
            // Update tampilan di popup
            var wrapper = document.querySelector('.foto-sambungan-wrapper');
            if (wrapper) {
                wrapper.innerHTML = 
                    '<div class="foto-sambungan-preview" id="sambunganFotoPreview">' +
                        '<img src="' + data.data.foto_url + '" alt="Foto Sambungan" id="sambunganFotoPreviewImg" onclick="lihatFotoFullscreen(\'' + data.data.foto_url + '\', \'Sambungan #' + sambunganIndex + '\', \'sambungan\')">' +
                        '<div class="foto-sambungan-actions">' +
                            '<button type="button" class="btn-foto-sambungan-change" onclick="triggerUploadFotoSambungan()" title="' + LANG.btn_change_photo + '">' +
                                '<i class="fa fa-refresh"></i>' +
                            '</button>' +
                            '<button type="button" class="btn-foto-sambungan-remove" onclick="hapusFotoSambungan(' + kabelId + ', ' + sambunganIndex + ')" title="' + LANG.btn_delete_photo + '">' +
                                '<i class="fa fa-trash"></i>' +
                            '</button>' +
                        '</div>' +
                    '</div>';
            }
            
            // Update marker icon di peta
            updateSambunganMarkerIcon(kabelId, sambunganIndex, true, data.data.foto_url);
            
            Swal.fire({
                icon: 'success',
                title: LANG.alert_success,
                text: LANG.alert_photo_uploaded,
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(function(error) {
        Swal.close();
        Swal.fire('Error', 'Gagal upload foto: ' + error.message, 'error');
    });
    
    // Reset input
    input.value = '';
}

// Hapus foto sambungan
function hapusFotoSambungan(kabelId, sambunganIndex) {
    Swal.fire({
        title: LANG.confirm_delete_photo,
        text: LANG.alert_photo_delete_permanent,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: LANG.btn_yes_delete,
        cancelButtonText: LANG.btn_cancel,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280'
    }).then(function(result) {
        if (result.isConfirmed) {
            Swal.fire({
                title: LANG.deleting,
                text: LANG.please_wait,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: function() { Swal.showLoading(); }
            });
            
            fetch(baseUrl + 'plugin/network_mapping/delete-foto-sambungan', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    kabel_id: kabelId,
                    sambungan_index: sambunganIndex
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                Swal.close();
                
                if (data.status === 'success') {
                    // Update tampilan di popup - kembali ke placeholder
                    var wrapper = document.querySelector('.foto-sambungan-wrapper');
                    if (wrapper) {
                        wrapper.innerHTML = 
                            '<div class="foto-sambungan-placeholder" id="sambunganFotoPlaceholder" onclick="triggerUploadFotoSambungan()">' +
                                '<i class="fa fa-camera"></i>' +
                                '<span>' + LANG.click_to_upload_photo + '</span>' +
                            '</div>';
                    }
                    
                    // Update marker icon di peta
                    updateSambunganMarkerIcon(kabelId, sambunganIndex, false, null);
                    
                    Swal.fire({
                        icon: 'success',
                        title: LANG.alert_success,
                        text: LANG.alert_photo_deleted,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(function(error) {
                Swal.close();
                Swal.fire('Error', 'Gagal menghapus foto: ' + error.message, 'error');
            });
        }
    });
}

// Update data marker sambungan setelah upload/hapus foto (icon tetap sama)
function updateSambunganMarkerIcon(kabelId, sambunganIndex, hasPhoto, fotoUrl) {
    // Cari kabel di networkData
    var kabel = networkData.kabel.find(function(k) { return k.id == kabelId; });
    if (!kabel || !kabel.sambunganMarkers) return;
    
    // Update data sambungan
    if (kabel.sambungan_data && kabel.sambungan_data[sambunganIndex - 1]) {
        if (hasPhoto) {
            kabel.sambungan_data[sambunganIndex - 1].foto = 'kabel_' + kabelId + '_sambungan_' + sambunganIndex + '.jpg';
            kabel.sambungan_data[sambunganIndex - 1].foto_url = fotoUrl;
        } else {
            kabel.sambungan_data[sambunganIndex - 1].foto = null;
            kabel.sambungan_data[sambunganIndex - 1].foto_url = null;
        }
    }
    
    // Update data di marker (icon tetap sama, tidak berubah warna)
    var marker = kabel.sambunganMarkers[sambunganIndex - 1];
    if (marker) {
        marker._sambunganData.foto = hasPhoto ? 'kabel_' + kabelId + '_sambungan_' + sambunganIndex + '.jpg' : null;
        marker._sambunganData.foto_url = fotoUrl;
    }
}
// Fungsi untuk pergi ke lokasi sambungan dan buka popup
function goToSambungan(kabelId, sambunganIndex) {
    // Tutup popup kabel yang sedang terbuka
    map.closePopup();
    
    // Cari kabel di networkData
    var kabel = networkData.kabel.find(function(k) { return k.id == kabelId; });
    if (!kabel || !kabel.sambunganMarkers) {
        Swal.fire('Error', 'Data sambungan tidak ditemukan', 'error');
        return;
    }
    
    // Cari marker sambungan berdasarkan index (index dimulai dari 1)
    var marker = kabel.sambunganMarkers[sambunganIndex - 1];
    if (!marker) {
        Swal.fire('Error', 'Marker sambungan tidak ditemukan', 'error');
        return;
    }
    
    // Dapatkan posisi marker
    var latlng = marker.getLatLng();
    
    // Selalu close up ke zoom level 21
    map.flyTo(latlng, 21, {
        duration: 0.8
    });
    
    // Setelah fly selesai, buka popup foto sambungan
    setTimeout(function() {
        if (marker._sambunganData) {
            tampilkanPopupFotoSambungan(marker._sambunganData);
        }
    }, 900);
}

// ============================================
// UNINSTALL FUNCTIONS
// ============================================

function showUninstallModal() {
    $('#uninstall_confirm').val('');
    $('#uninstall-progress-section').hide();
    $('#uninstall-log-content').html('');
    $('#btn-run-uninstall').prop('disabled', false).html('<i class="fa fa-trash"></i> ' + LANG.btn_uninstall_plugin);
    $('#btn-cancel-uninstall').show();
    
    var overlay = document.getElementById('uninstallModalOverlay');
    overlay.style.display = 'flex';
    overlay.offsetHeight; // Force reflow
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeUninstallModal() {
    var overlay = document.getElementById('uninstallModalOverlay');
    overlay.classList.remove('show');
    setTimeout(function() {
        overlay.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
}

// Close modal on overlay click
document.addEventListener('click', function(e) {
    if (e.target.id === 'uninstallModalOverlay') {
        closeUninstallModal();
    }
    if (e.target.id === 'updateModalOverlay') {
        closeUpdateModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('uninstallModalOverlay').classList.contains('show')) {
            closeUninstallModal();
        }
        if (document.getElementById('updateModalOverlay').classList.contains('show')) {
            closeUpdateModal();
        }
    }
});

function runUninstall() {
    var confirmText = $('#uninstall_confirm').val().trim();
    
    if (confirmText !== 'UNINSTALL') {
        Swal.fire({
            title: LANG.confirm_required,
            text: LANG.type_uninstall_to_continue,
            icon: 'warning'
        });
        return;
    }
    
    // Double confirmation
    Swal.fire({
        title: LANG.confirm_final,
        html: '<p>' + LANG.are_you_sure + '</p><p><strong>' + LANG.action_cannot_be_undone + '</strong></p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: LANG.btn_yes_uninstall_now,
        cancelButtonText: LANG.btn_cancel
    }).then(function(result) {
        if (result.isConfirmed) {
            executeUninstall();
        }
    });
}

function executeUninstall() {
    // Disable buttons
    $('#btn-run-uninstall').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + LANG.uninstalling);
    $('#btn-cancel-uninstall').hide();
    
    // Show progress log
    $('#uninstall-progress-section').slideDown(300);
    addUninstallLog(LANG.starting_uninstall, 'info');
    
    $.ajax({
        url: baseUrl + 'plugin/network_mapping/uninstall',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show all details with animation
                if (response.details) {
                    response.details.forEach(function(detail, index) {
                        setTimeout(function() {
                            addUninstallLog(detail, 'success');
                        }, index * 300);
                    });
                }
                
                // Show success and redirect
                var totalDelay = (response.details ? response.details.length * 300 : 0) + 1000;
                setTimeout(function() {
                    closeUninstallModal();
                    Swal.fire({
                        title: LANG.alert_success,
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: LANG.btn_to_dashboard
                    }).then(function() {
                        window.location.href = baseUrl + 'dashboard';
                    });
                }, totalDelay);
            } else {
                addUninstallLog('Error: ' + response.message, 'error');
                $('#btn-run-uninstall').prop('disabled', false).html('<i class="fa fa-trash"></i> ' + LANG.btn_retry);
                $('#btn-cancel-uninstall').show();
                
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            addUninstallLog('Koneksi error: ' + error, 'error');
            $('#btn-run-uninstall').prop('disabled', false).html('<i class="fa fa-trash"></i> ' + LANG.btn_retry);
            $('#btn-cancel-uninstall').show();
            
            Swal.fire('Error!', 'Gagal terhubung ke server', 'error');
        }
    });
}

function addUninstallLog(message, type) {
    var iconClass = type === 'success' ? 'fa-check' : 
                    type === 'error' ? 'fa-times' : 
                    'fa-circle-o-notch fa-spin';
    
    var html = '<div class="log-item ' + type + '">' +
               '<i class="fa ' + iconClass + '"></i> ' +
               '<span>' + message + '</span>' +
               '</div>';
    
    $('#uninstall-log-content').append(html);
    $('#uninstall-log-content').scrollTop($('#uninstall-log-content')[0].scrollHeight);
}

// ============================================
// PLUGIN SETTINGS MENU FUNCTIONS
// ============================================

function togglePluginMenu() {
    var fab = document.getElementById('pluginSettingsFab');
    var menu = document.getElementById('pluginSettingsMenu');
    
    fab.classList.toggle('active');
    menu.classList.toggle('show');
}

// Close plugin menu when clicking outside
document.addEventListener('click', function(e) {
    var container = document.getElementById('pluginSettingsFab');
    var menu = document.getElementById('pluginSettingsMenu');
    
    if (container && menu) {
        var isClickInside = container.contains(e.target) || menu.contains(e.target);
        
        if (!isClickInside && menu.classList.contains('show')) {
            menu.classList.remove('show');
            container.classList.remove('active');
        }
    }
});

// ============================================
// UPDATE FUNCTIONS
// ============================================

var updateData = null;

// Helper functions untuk localStorage update notification
function shouldShowUpdateNotification() {
    var lastCheck = localStorage.getItem('ffth_last_update_check');
    var lastVersion = localStorage.getItem('ffth_last_checked_version');
    
    if (!lastCheck) return true;
    
    var now = new Date().getTime();
    var lastCheckTime = parseInt(lastCheck);
    var hoursSinceLastCheck = (now - lastCheckTime) / (1000 * 60 * 60);
    
    // Get current version from page
    var currentVersion = typeof ffthCurrentVersion !== 'undefined' ? ffthCurrentVersion : null;
    
    // Show notification if:
    // 1. More than 24 hours since last check
    // 2. OR version has changed
    return hoursSinceLastCheck > 24 || (currentVersion && lastVersion !== currentVersion);
}

function markUpdateNotificationShown(version) {
    localStorage.setItem('ffth_last_update_check', new Date().getTime().toString());
    localStorage.setItem('ffth_last_checked_version', version);
}

function hasSeenUpdateModal(version) {
    var seenVersion = localStorage.getItem('ffth_update_modal_seen');
    return seenVersion === version;
}

function markUpdateModalSeen(version) {
    localStorage.setItem('ffth_update_modal_seen', version);
}

function checkForUpdate(isManualCheck) {
    var btn = $('#btn-check-update');
    var statusText = $('#update-status');
    var iconEl = btn.find('.action-icon i');
    
    // Show loading only if manual check
    if (isManualCheck) {
        iconEl.removeClass('fa-refresh').addClass('fa-spinner fa-spin');
        statusText.text(LANG.checking);
    }
    
    $.ajax({
        url: baseUrl + 'plugin/network_mapping/check-update',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (isManualCheck) {
                iconEl.removeClass('fa-spinner fa-spin').addClass('fa-refresh');
            }
            
            if (response.success) {
                if (response.has_update) {
                    // Has update available
                    updateData = response;
                    statusText.text('v' + response.latest_version + ' ' + LANG.available_update);
                    
                    // Show update badge on FAB (red dot only)
                    var badge = document.getElementById('pluginUpdateBadge');
                    if (badge) badge.style.display = 'block';
                    
                    // Add has-update class to update button item (blinking green)
                    var updateBtn = document.getElementById('btn-check-update');
                    if (updateBtn) updateBtn.classList.add('has-update');
                    
                    // Show update modal only if:
                    // 1. Manual check OR
                    // 2. Auto check AND haven't seen this version's modal yet
                    if (isManualCheck || !hasSeenUpdateModal(response.latest_version)) {
                        showUpdateModal(response);
                        if (!isManualCheck) {
                            markUpdateModalSeen(response.latest_version);
                        }
                    }
                } else {
                    // Already latest
                    btn.removeClass('has-update');
                    statusText.text(LANG.up_to_date + ' ✓');
                    
                    // Show SweetAlert only if manual check OR should show notification
                    if (isManualCheck || shouldShowUpdateNotification()) {
                        Swal.fire({
                            icon: 'success',
                            title: LANG.up_to_date,
                            text: LANG.using_latest_version.replace('__VERSION__', response.current_version),
                            timer: 3000,
                            showConfirmButton: false
                        });
                        
                        // Mark as shown
                        if (!isManualCheck) {
                            markUpdateNotificationShown(response.current_version);
                        }
                    }
                    
                    // Reset text after 5 seconds
                    setTimeout(function() {
                        statusText.text(LANG.click_to_check);
                    }, 5000);
                }
            } else {
                if (isManualCheck) {
                    statusText.text(LANG.check_failed);
                    Swal.fire(LANG.alert_error, response.message, 'error');
                }
            }
        },
        error: function() {
            if (isManualCheck) {
                iconEl.removeClass('fa-spinner fa-spin').addClass('fa-refresh');
                statusText.text(LANG.check_failed);
                Swal.fire(LANG.alert_error, LANG.failed_check_update, 'error');
            }
        }
    });
}

function showUpdateModal(data) {
    // Set versions
    $('#current-version').text('v' + data.current_version);
    $('#new-version').text('v' + data.latest_version);
    
    // Set changelog
    var changelogHtml = '';
    if (data.changelog && data.changelog.length > 0) {
        data.changelog.forEach(function(item) {
            changelogHtml += '<div class="changelog-item-ffth"><i class="fa fa-check-circle"></i><span>' + item + '</span></div>';
        });
    } else {
        changelogHtml = '<div class="changelog-item-ffth"><i class="fa fa-info-circle"></i><span>No changelog available</span></div>';
    }
    $('#changelog-list').html(changelogHtml);
    
    // Reset UI
    $('#update-progress-section').hide();
    $('#update-log-content').html('');
    $('#btn-run-update').prop('disabled', false).html('<i class="fa fa-download"></i> ' + LANG.btn_update_now);
    $('#btn-cancel-update').show();
    
    // Show modal
    var overlay = document.getElementById('updateModalOverlay');
    overlay.style.display = 'flex';
    overlay.offsetHeight; // Force reflow
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeUpdateModal() {
    var overlay = document.getElementById('updateModalOverlay');
    overlay.classList.remove('show');
    setTimeout(function() {
        overlay.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
}

function runUpdate() {
    // Disable buttons
    $('#btn-run-update').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + LANG.updating);
    $('#btn-cancel-update').hide();
    
    // Show progress
    $('#update-progress-section').slideDown(300);
    addUpdateLog(LANG.starting_update, 'info');
    
    $.ajax({
        url: baseUrl + 'plugin/network_mapping/run-update',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show all details with animation
                if (response.details) {
                    response.details.forEach(function(detail, index) {
                        setTimeout(function() {
                            addUpdateLog(detail, 'success');
                        }, index * 300);
                    });
                }
                
                // Show success and reload
                var totalDelay = (response.details ? response.details.length * 300 : 0) + 1000;
                setTimeout(function() {
                    closeUpdateModal();
                    
                    // Clear localStorage untuk update notification
                    localStorage.removeItem('ffth_update_modal_seen');
                    localStorage.removeItem('ffth_last_update_check');
                    localStorage.removeItem('ffth_last_checked_version');
                    
                    Swal.fire({
                        title: LANG.update_complete,
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: LANG.btn_reload_page
                    }).then(function() {
                        location.reload();
                    });
                }, totalDelay);
            } else {
                addUpdateLog('Error: ' + response.message, 'error');
                $('#btn-run-update').prop('disabled', false).html('<i class="fa fa-download"></i> ' + LANG.btn_retry);
                $('#btn-cancel-update').show();
                
                Swal.fire(LANG.update_failed, response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            addUpdateLog(LANG.connection_error + ': ' + error, 'error');
            $('#btn-run-update').prop('disabled', false).html('<i class="fa fa-download"></i> ' + LANG.btn_retry);
            $('#btn-cancel-update').show();
            
            Swal.fire(LANG.alert_error, LANG.failed_connect_server, 'error');
        }
    });
}

function addUpdateLog(message, type) {
    var iconClass = type === 'success' ? 'fa-check' : 
                    type === 'error' ? 'fa-times' : 
                    'fa-circle-o-notch fa-spin';
    
    var html = '<div class="log-item ' + type + '">' +
               '<i class="fa ' + iconClass + '"></i> ' +
               '<span>' + message + '</span>' +
               '</div>';
    
    $('#update-log-content').append(html);
    $('#update-log-content').scrollTop($('#update-log-content')[0].scrollHeight);
}

// Auto-check for updates on page load
// Menggunakan window.onload untuk memastikan semua script (termasuk jQuery dari footer) sudah ter-load
window.addEventListener('load', function() {
    setTimeout(function() {
        if (typeof jQuery !== 'undefined' && typeof checkForUpdate === 'function') {
            checkForUpdate(false);  // false = auto check (tidak manual)
        } else {
            console.log('FFTH Mapping: jQuery not available, skipping auto-check');
        }
    }, 3000);
});
</script>

<style>

/* Plugin Settings Button (Inside Map) */
.plugin-settings-container {
    position: absolute;
    bottom: 20px;
    left: 20px;
    z-index: 1000;
}

.plugin-settings-fab {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #fff;
    border: 2px solid #6366f1;
    color: #6366f1;
    font-size: 20px;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.plugin-settings-fab:hover {
    background: #f5f3ff;
    border-color: #6366f1;
    transform: scale(1.05);
}

.plugin-settings-fab.active {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
    border-color: #6366f1;
}

.plugin-settings-fab.active i {
    animation: rotate-gear 0.5s ease;
}

@keyframes rotate-gear {
    from { transform: rotate(0deg); }
    to { transform: rotate(90deg); }
}

/* Update Badge */
.plugin-update-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 12px;
    height: 12px;
    background: #ef4444;
    border-radius: 50%;
    border: 2px solid #fff;
    animation: pulse-badge 2s infinite;
}

@keyframes pulse-badge {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

/* Plugin Settings Menu */
.plugin-settings-menu {
    position: absolute;
    bottom: 54px;
    left: 0;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    min-width: 240px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px) scale(0.95);
    transition: all 0.25s ease;
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

.plugin-settings-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
}

.plugin-settings-header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
    padding: 12px 14px;
    font-weight: 600;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.plugin-settings-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border: none;
    background: #fff;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
    text-align: left;
    border-bottom: 1px solid #f3f4f6;
}

.plugin-settings-item:last-child {
    border-bottom: none;
}

.plugin-settings-item:hover {
    background: #f9fafb;
}

.plugin-settings-item.uninstall:hover {
    background: #fef2f2;
}

/* Update Item - Has Update State (Blinking Green) */
.plugin-settings-item.has-update {
    animation: pulse-update-item 2s infinite;
    border-left: 3px solid #10b981;
}

.plugin-settings-item.has-update .plugin-settings-icon.update {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
}

.plugin-settings-item.has-update .plugin-settings-desc {
    color: #10b981;
    font-weight: 600;
}

@keyframes pulse-update-item {
    0%, 100% { 
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
        background: #f0fdf4;
    }
    50% { 
        box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
        background: #dcfce7;
    }
}

/* Dark Mode - Has Update */
.dark-mode .plugin-settings-item.has-update,
body.dark-mode .plugin-settings-item.has-update {
    border-left-color: #10b981;
}

.dark-mode .plugin-settings-item.has-update .plugin-settings-desc,
body.dark-mode .plugin-settings-item.has-update .plugin-settings-desc {
    color: #34d399;
}

@keyframes pulse-update-item {
    0%, 100% { 
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
    }
    50% { 
        box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
    }
}

.plugin-settings-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}

.plugin-settings-icon.update {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #2563eb;
}

.plugin-settings-icon.uninstall {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #dc2626;
}

.plugin-settings-info {
    display: flex;
    flex-direction: column;
}

.plugin-settings-title {
    font-weight: 600;
    font-size: 13px;
    color: #1f2937;
}

.plugin-settings-desc {
    font-size: 11px;
    color: #6b7280;
}

/* Dark Mode Support */
.dark-mode .plugin-settings-fab,
body.dark-mode .plugin-settings-fab {
    background: #1e293b;
    border-color: #6366f1;
    color: #a5b4fc;
}

.dark-mode .plugin-settings-fab:hover,
body.dark-mode .plugin-settings-fab:hover {
    background: #334155;
    border-color: #6366f1;
}

.dark-mode .plugin-settings-fab.active,
body.dark-mode .plugin-settings-fab.active {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
}

.dark-mode .plugin-settings-menu,
body.dark-mode .plugin-settings-menu {
    background: #1e293b;
    border-color: #475569;
}

.dark-mode .plugin-settings-item,
body.dark-mode .plugin-settings-item {
    background: #1e293b;
    border-color: #334155;
}

.dark-mode .plugin-settings-item:hover,
body.dark-mode .plugin-settings-item:hover {
    background: #334155;
}

.dark-mode .plugin-settings-title,
body.dark-mode .plugin-settings-title {
    color: #f1f5f9;
}

.dark-mode .plugin-settings-desc,
body.dark-mode .plugin-settings-desc {
    color: #94a3b8;
}

.dark-mode .plugin-update-badge,
body.dark-mode .plugin-update-badge {
    border-color: #1e293b;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .plugin-settings-container {
        bottom: 15px;
        left: 15px;
    }
    
    .plugin-settings-fab {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
    
    .plugin-settings-menu {
        min-width: 220px;
        bottom: 50px;
    }
}

/* ============================================
   MODAL STYLES
   ============================================ */

.modal-overlay-ffth {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    overflow-y: auto;
    padding: 20px;
    box-sizing: border-box;
}

.modal-overlay-ffth.show {
    display: flex !important;
    opacity: 1;
}

.modal-overlay-ffth.show {
    opacity: 1;
}

.modal-container-ffth {
    background: #fff;
    border-radius: 16px;
    width: 95%;
    max-width: 520px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    transform: translateY(20px);
    transition: transform 0.3s ease;
}

.modal-overlay-ffth.show .modal-container-ffth {
    transform: translateY(0);
}

/* Header Danger (Uninstall) */
.modal-header-danger-ffth {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
    padding: 18px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header-danger-ffth h4 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Header Update */
.modal-header-update-ffth {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff;
    padding: 18px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header-update-ffth h4 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-close-btn-ffth {
    background: rgba(255,255,255,0.2);
    border: none;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.modal-close-btn-ffth:hover {
    background: rgba(255,255,255,0.3);
}

.modal-body-ffth {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer-ffth {
    padding: 16px 20px;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* Warning Box */
.warning-box-ffth {
    display: flex;
    gap: 14px;
    padding: 16px;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid #f59e0b;
}

.warning-icon-ffth {
    width: 44px;
    height: 44px;
    background: #f59e0b;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 20px;
    flex-shrink: 0;
}

.warning-content-ffth strong {
    display: block;
    color: #92400e;
    margin-bottom: 4px;
    font-size: 15px;
}

.warning-content-ffth p {
    margin: 0;
    color: #a16207;
    font-size: 13px;
    line-height: 1.5;
}

/* Form Section */
.form-section-ffth {
    background: #f9fafb;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    border: 1px solid #e5e7eb;
}

.form-section-header-ffth {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 14px;
    color: #374151;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e7eb;
}

.form-section-header-ffth i {
    color: #6b7280;
}

/* Delete List Grid */
.delete-list-grid-ffth {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.delete-item-ffth {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: #fff;
    border-radius: 8px;
    font-size: 12px;
    color: #374151;
    border: 1px solid #e5e7eb;
}

.delete-item-ffth i {
    font-size: 14px;
    width: 20px;
    text-align: center;
}

/* Form Controls */
.form-group-ffth {
    margin-bottom: 0;
}

.form-group-ffth label {
    display: block;
    font-size: 13px;
    color: #4b5563;
    margin-bottom: 8px;
}

.confirm-code-ffth {
    background: #fee2e2;
    color: #dc2626;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 600;
}

.form-control-ffth {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
    box-sizing: border-box;
}

.form-control-ffth:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Progress Log */
.progress-log-container-ffth {
    background: #1f2937;
    border-radius: 8px;
    padding: 14px;
    max-height: 180px;
    overflow-y: auto;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 12px;
}

.progress-log-container-ffth .log-item {
    padding: 6px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #9ca3af;
}

.progress-log-container-ffth .log-item.success {
    color: #4ade80;
}

.progress-log-container-ffth .log-item.error {
    color: #f87171;
}

.progress-log-container-ffth .log-item.info {
    color: #60a5fa;
}

/* Buttons */
.btn-cancel-ffth {
    padding: 10px 20px;
    border: 1px solid #d1d5db;
    background: #fff;
    color: #374151;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.btn-cancel-ffth:hover {
    background: #f3f4f6;
}

.btn-danger-ffth {
    padding: 10px 20px;
    border: none;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.btn-danger-ffth:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.btn-danger-ffth:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-update-ffth {
    padding: 10px 20px;
    border: none;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.btn-update-ffth:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-update-ffth:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Version Box */
.update-version-box-ffth {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    padding: 20px;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid #bfdbfe;
}

.version-current-ffth,
.version-new-ffth {
    text-align: center;
}

.version-label-ffth {
    display: block;
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 4px;
}

.version-number-ffth {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
}

.version-new-ffth .version-number-ffth {
    color: #2563eb;
}

.version-arrow-ffth {
    font-size: 24px;
    color: #9ca3af;
}

/* Changelog List */
.changelog-list-ffth {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.changelog-item-ffth {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    background: #fff;
    border-radius: 8px;
    font-size: 13px;
    color: #374151;
    border: 1px solid #e5e7eb;
}

.changelog-item-ffth i {
    color: #22c55e;
    margin-top: 2px;
}

/* Responsive */
@media (max-width: 768px) {
    .plugin-actions-body {
        flex-direction: column;
    }
    
    .plugin-action-btn {
        max-width: 100%;
    }
    
    .delete-list-grid-ffth {
        grid-template-columns: 1fr;
    }
    
    .update-version-box-ffth {
        flex-direction: column;
        gap: 12px;
    }
    
    .version-arrow-ffth {
        transform: rotate(90deg);
    }
    
    .modal-container-ffth {
        width: 95%;
        margin: 10px;
    }
    
    .modal-footer-ffth {
        flex-direction: column;
    }
    
    .btn-cancel-ffth,
    .btn-danger-ffth,
    .btn-update-ffth {
        width: 100%;
        justify-content: center;
    }
}

/* Dark Mode Support */
.dark-mode .plugin-actions-panel,
body.dark-mode .plugin-actions-panel {
    background: #1e293b;
    border-color: #334155;
}

.dark-mode .plugin-action-btn,
body.dark-mode .plugin-action-btn {
    background: #0f172a;
    border-color: #334155;
}

.dark-mode .plugin-action-btn .action-title,
body.dark-mode .plugin-action-btn .action-title {
    color: #f1f5f9;
}

.dark-mode .plugin-action-btn .action-desc,
body.dark-mode .plugin-action-btn .action-desc {
    color: #94a3b8;
}

.dark-mode .modal-container-ffth,
body.dark-mode .modal-container-ffth {
    background: #1e293b;
}

.dark-mode .modal-body-ffth,
body.dark-mode .modal-body-ffth {
    color: #f1f5f9;
}

.dark-mode .form-section-ffth,
body.dark-mode .form-section-ffth {
    background: #0f172a;
    border-color: #334155;
}

.dark-mode .form-section-header-ffth,
body.dark-mode .form-section-header-ffth {
    color: #f1f5f9;
    border-color: #334155;
}

.dark-mode .delete-item-ffth,
body.dark-mode .delete-item-ffth {
    background: #1e293b;
    border-color: #334155;
    color: #e2e8f0;
}

.dark-mode .form-control-ffth,
body.dark-mode .form-control-ffth {
    background: #0f172a;
    border-color: #334155;
    color: #f1f5f9;
}

.dark-mode .modal-footer-ffth,
body.dark-mode .modal-footer-ffth {
    background: #0f172a;
    border-color: #334155;
}

.dark-mode .warning-content-ffth strong,
body.dark-mode .warning-content-ffth strong {
    color: #fbbf24;
}

.dark-mode .warning-content-ffth p,
body.dark-mode .warning-content-ffth p {
    color: #fcd34d;
}

/* SweetAlert Z-Index Fix - Agar muncul di atas modal */
.swal2-container {
    z-index: 999999 !important;
}

.swal2-popup {
    z-index: 999999 !important;
}

/* Pastikan backdrop SweetAlert juga di atas */
.swal2-container.swal2-backdrop-show {
    z-index: 999999 !important;
}

/* Language Switcher in Plugin Settings */
.plugin-settings-item.language-container {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    gap: 12px;
    cursor: default;
}

.plugin-settings-item.language-container:hover {
    background: transparent;
}

.language-buttons {
    display: flex;
    gap: 6px;
    margin-left: auto;
}

.lang-btn {
    padding: 5px 14px;
    border: 1.5px solid #d1d5db;
    background: #ffffff;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.lang-btn:hover {
    border-color: #9ca3af;
    background: #f9fafb;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.lang-btn.active {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-color: #3b82f6;
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(59, 130, 246, 0.4);
}

.lang-btn.active:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    transform: translateY(-1px);
}

/* Icon Language - Hijau transparan PERSIS seperti Update & Uninstall */
.plugin-settings-icon.language {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #059669;
}

/* Dark Mode */
.dark-mode .lang-btn,
body.dark-mode .lang-btn {
    background: #1e293b;
    border-color: #475569;
    color: #cbd5e1;
}

.dark-mode .lang-btn:hover,
body.dark-mode .lang-btn:hover {
    background: #334155;
    border-color: #64748b;
}

.dark-mode .lang-btn.active,
body.dark-mode .lang-btn.active {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-color: #3b82f6;
    color: #ffffff;
}
</style>

<script>
function switchLanguage(lang) {
    $.ajax({
        url: 'index.php?_route=plugin/network_mapping/switch-language',
        type: 'POST',
        data: { language: lang },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Failed to switch language');
            }
        },
        error: function() {
            alert('Error switching language');
        }
    });
}
</script>

{include file="sections/footer.tpl"}
