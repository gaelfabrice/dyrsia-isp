{if $wz_use_page_shell|default:false}</div>{/if}
</section>
</div>

<footer class="main-footer">
    <div class="pull-right" id="version"
         onclick="location.href = '{Text::url("community")}#latestVersion';"></div>

    DYRSIA Powered by
    <a href="#" rel="nofollow noreferrer noopener">
        Dyrsia
    </a>
</footer>

</div>

<script src="{$app_url}/ui/ui/scripts/jquery.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/bootstrap.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/adminlte.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/plugins/select2.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/pace.min.js"></script>
<script src="{$app_url}/ui/ui/summernote/summernote.min.js"></script>
<script src="{$app_url}/ui/ui/scripts/custom.js?2025.2.5"></script>
{if $_c['router_check'] == '1'}
<style>
    #router-alert-toasts {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: min(360px, calc(100vw - 36px));
        pointer-events: none;
    }
    .router-alert-toast {
        pointer-events: auto;
        background: #1e293b;
        color: #f8fafc;
        border: 1px solid rgba(248, 113, 113, 0.45);
        border-left: 4px solid #ef4444;
        border-radius: 12px;
        padding: 14px 14px 10px;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35);
        animation: routerAlertIn 0.35s ease;
        cursor: pointer;
    }
    .router-alert-toast-body small { color: #94a3b8; }
    .router-alert-toast-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 10px;
    }
    .router-alert-link {
        color: #67e8f9;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
    }
    .router-alert-close {
        background: transparent;
        border: 0;
        color: #94a3b8;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
        padding: 0 4px;
    }
    .router-alert-close:hover { color: #fff; }
    .router-alert-toast-hide {
        opacity: 0;
        transform: translateX(12px);
        transition: opacity 0.25s, transform 0.25s;
    }
    @keyframes routerAlertIn {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
<script>
    window.ROUTER_ALERTS_URL = '{Text::url('routers/alerts')}';
    window.ROUTER_DISMISS_URL = '{Text::url('routers/dismiss-alert')}';
    window.ROUTER_LIST_URL = '{Text::url('routers/list')}';
</script>
<script src="{$app_url}/ui/ui/scripts/router-alerts.js?2025.5.22"></script>
{/if}

<script>
/* =========================
   SEARCH OVERLAY
========================= */
document.addEventListener("DOMContentLoaded", function () {

    const openSearch = document.getElementById('openSearch');
    const closeSearch = document.getElementById('closeSearch');
    const searchOverlay = document.getElementById('searchOverlay');
    const searchTerm = document.getElementById('searchTerm');

    function openSearchOverlay() {
        if (!searchOverlay) return;
        searchOverlay.classList.add('is-open');
        searchOverlay.setAttribute('aria-hidden', 'false');
        if (searchTerm) {
            setTimeout(function () { searchTerm.focus(); }, 50);
        }
    }

    function closeSearchOverlay() {
        if (!searchOverlay) return;
        searchOverlay.classList.remove('is-open');
        searchOverlay.setAttribute('aria-hidden', 'true');
    }

    if (openSearch && searchOverlay) {
        openSearch.addEventListener('click', function (e) {
            e.preventDefault();
            openSearchOverlay();
        });
    }

    if (closeSearch && searchOverlay) {
        closeSearch.addEventListener('click', function () {
            closeSearchOverlay();
        });
    }

    if (searchOverlay) {
        searchOverlay.addEventListener('click', function (e) {
            if (e.target === searchOverlay) {
                closeSearchOverlay();
            }
        });
    }

    if (searchTerm) {
        searchTerm.addEventListener('keyup', function () {
            let query = this.value;

            $.ajax({
                url: "{Text::url("search_user")}",
                type: "GET",
                data: { query: query },
                success: function (data) {
                    if (data.trim() !== "") {
                        $('#searchResults').html(data).show();
                    } else {
                        $('#searchResults').html('').hide();
                    }
                }
            });
        });
    }

});
</script>

<script>
/* =========================
   DARK / LIGHT MODE
========================= */
const toggleIcon = document.getElementById('toggleIcon');
const body = document.body;

const savedMode = localStorage.getItem('mode');

function setMode(mode) {
    if (!toggleIcon) return;
    if (mode === 'dark') {
        body.classList.add('dark-mode');
        toggleIcon.classList.remove('fa-moon-o');
        toggleIcon.classList.add('fa-sun-o');
    } else {
        body.classList.remove('dark-mode');
        toggleIcon.classList.remove('fa-sun-o');
        toggleIcon.classList.add('fa-moon-o');
    }
}

if (savedMode === 'dark') {
    setMode('dark');
} else {
    setMode('light');
}

document.querySelectorAll('.toggle-container').forEach(function (el) {
    el.addEventListener('click', function (e) {
        e.preventDefault();
        if (body.classList.contains('dark-mode')) {
            setMode('light');
            localStorage.setItem('mode', 'light');
        } else {
            setMode('dark');
            localStorage.setItem('mode', 'dark');
        }
    });
});
</script>

{if isset($xfooter)}
    {$xfooter}
{/if}

<script>
var CSRF_TOKEN = '{$csrf_token|escape:'javascript'}';
</script>
{literal}
<script>
var listAttApi;
var posAttApi = 0;

$(document).ready(function () {

    $('.select2').select2({ theme: "bootstrap" });
    $('.select2tag').select2({ theme: "bootstrap", tags: true });

    /* =========================
       BUTTON LOADING EFFECT
    ========================= */
    document.querySelectorAll('button[type="submit"]').forEach(function (el) {

        el.addEventListener("click", function () {
            var txt = $(this).html();

            $(this).html(`<span class="loading"></span>`);

            setTimeout(() => {
                $(this).prop("disabled", true);
            }, 100);

            setTimeout(() => {
                $(this).html(txt);
                $(this).prop("disabled", false);
            }, 5000);
        });

    });

    setTimeout(() => {
        listAttApi = document.querySelectorAll(`[api-get-text]`);
        apiGetText();
    }, 500);

});

/* =========================
   API TEXT LOADER
========================= */
function apiGetText() {
    var el = listAttApi[posAttApi];

    if (el != undefined) {
        $.get(el.getAttribute('api-get-text'), function (data) {
            el.innerHTML = data;
            posAttApi++;
            if (posAttApi < listAttApi.length) {
                apiGetText();
            }
        });
    }
}

/* =========================
   SIDEBAR COLLAPSE COOKIE
========================= */
function setKolaps() {
    var kolaps = getCookie('kolaps');

    if (kolaps) {
        setCookie('kolaps', false, 30);
    } else {
        setCookie('kolaps', true, 30);
    }
    return true;
}

function setCookie(name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 86400000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');

    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];

        while (c.charAt(0) == ' ') c = c.substring(1, c.length);

        if (c.indexOf(nameEQ) == 0)
            return c.substring(nameEQ.length, c.length);
    }

    return null;
}

/* =========================
   CONFIRM DELETE / ACTION
========================= */
function ask(field, text) {
    var txt = field.innerHTML;
    if (confirm(text)) {
        setTimeout(function () {
            field.innerHTML = field.innerHTML.replace('<span class="loading"></span>', txt);
            field.removeAttribute('disabled');
        }, 5000);
        return true;
    }
    setTimeout(function () {
        field.innerHTML = field.innerHTML.replace('<span class="loading"></span>', txt);
        field.removeAttribute('disabled');
    }, 500);
    return false;
}

/* =========================
   TOOLTIP + POPOVER
========================= */
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
    $("[data-toggle=popover]").popover();
});

/* Auto-inject CSRF token into admin POST forms */
(function () {
    var token = (typeof CSRF_TOKEN !== 'undefined') ? CSRF_TOKEN : '';
    if (!token) {
        return;
    }
    document.querySelectorAll('form').forEach(function (form) {
        var method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'post' || form.querySelector('input[name="csrf_token"]')) {
            return;
        }
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = token;
        form.appendChild(input);
    });
})();
</script>
{/literal}

</body>
</html>