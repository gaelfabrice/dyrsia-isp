<script>
    function hotspotTestNavigate(url, panelId) {
        try { sessionStorage.setItem('wifizone_hotspot_panel', panelId); } catch (e) {}
        if (history.replaceState) { history.replaceState(null, '', '#' + panelId); }
        window.location.href = url;
    }
    function hotspotTestTg() {
        hotspotTestNavigate('{$_url}plugin/hotspot_config&testTg=test', 'collapseHotspotTg');
    }
    function hotspotTestSms() {
        var t = prompt("{Lang::T('Phone number')}\n{Lang::T('Save First before Test')}", "");
        if (t) hotspotTestNavigate('{$_url}plugin/hotspot_config&testSms=' + encodeURIComponent(t), 'collapseHotspotSms');
    }
    function hotspotTestWa() {
        var t = prompt("{Lang::T('Phone number')}\n{Lang::T('Save First before Test')}", "");
        if (t) hotspotTestNavigate('{$_url}plugin/hotspot_config&testWa=' + encodeURIComponent(t), 'collapseHotspotWa');
    }
    function hotspotTestEmail() {
        var t = prompt("{Lang::T('Email')}\n{Lang::T('Save First before Test')}", "");
        if (t) hotspotTestNavigate('{$_url}plugin/hotspot_config&testEmail=' + encodeURIComponent(t), 'collapseHotspotEmail');
    }
    document.addEventListener('DOMContentLoaded', function() {
        var panelId = (location.hash || '').replace('#', '');
        if (!panelId) {
            try { panelId = sessionStorage.getItem('wifizone_hotspot_panel') || ''; } catch (e) {}
        }
        if (panelId) {
            var el = document.getElementById(panelId);
            if (el) {
                var body = el.querySelector('.panel-collapse');
                if (body && typeof $ !== 'undefined') $(body).collapse('show');
                setTimeout(function() { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 300);
            }
        }
    });
</script>
