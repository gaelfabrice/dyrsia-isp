<div class="box box-solid exceptional-box insight-box-final">
    <div class="box-header">
        <div class="header-icon-large"><i class="fa fa-pie-chart"></i></div>
        <h3 class="box-title">{Lang::T('All Users Insights')}</h3>
    </div>

    <div class="box-body theme-body-insight">
        <div class="chart-container">
            <canvas id="userRechargesChart"></canvas>
        </div>
    </div>
</div>

<style>
/* ==========================================
   INSIGHT BOX STYLE - FINAL STRIP FIX
========================================== */
.insight-box-final {
    background: #ffffff !important;
    color: #333;
    border-radius: 6px !important;
    margin-bottom: 20px !important;
    position: relative;
    overflow: hidden !important; 
    border: 1px solid rgba(0,0,0,0.08) !important;
    /* হোয়াইট মোডেও একই স্ট্রিপ লুক নিশ্চিত করা */
    box-shadow: inset 0 4px 0 #ff2e93, 0 6px 15px rgba(0,0,0,0.05) !important;
}

/* ওপরে সেই সিগনেচার পিঙ্ক-ব্লু গ্রেডিয়েন্ট স্ট্রিপ */
.insight-box-final::before {
    content: "" !important;
    position: absolute !important;
    left: 20% !important; 
    top: 0 !important; 
    width: 60% !important; 
    height: 4px !important;
    background: linear-gradient(90deg, #ff2e93 0%, #3b82f6 100%) !important;
    border-radius: 0 0 10px 10px !important;
    z-index: 999 !important; /* যাতে অন্য কিছু একে ঢাকতে না পারে */
}

/* ডার্ক মোড ফিক্স */
body.dark-mode .insight-box-final {
    background: #161b2e !important;
    color: #ffffff !important;
    border: 1px solid #232a42 !important;
    box-shadow: inset 0 4px 0 #ff2e93, 0 10px 25px rgba(0,0,0,0.3) !important;
}

/* বড় আইকন স্টাইল (32px) */
.header-icon-large i {
    font-size: 32px !important; 
    color: #00bcd4 !important; 
    margin-right: 15px;
    display: inline-block;
    vertical-align: middle;
}

.insight-box-final .box-header {
    padding: 15px 20px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.insight-box-final .box-title {
    font-size: 16px !important;
    font-weight: 700;
}

.chart-container {
    position: relative;
    height: 320px;
    width: 100%;
    padding: 10px;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var u_act = '{$u_act}';
    var c_all = '{$c_all}';
    var u_all = '{$u_all}';

    var expired = parseInt(u_all) - parseInt(u_act);
    var inactive = parseInt(c_all) - parseInt(u_all);
    if (inactive < 0) inactive = 0;

    const body = document.body;
    const isDark = body.classList.contains('dark-mode') || body.classList.contains('dark');

    var ctx = document.getElementById('userRechargesChart').getContext('2d');

    var chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Active Users', 'Expired Users', 'Inactive Users'],
            datasets: [{
                data: [parseInt(u_act), expired, inactive],
                backgroundColor: ['#00d084', '#ff2e93', '#3b82f6'],
                borderWidth: 0,
                hoverOffset: 15
            }]
        },
        options: {
            cutout: '55%',
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: isDark ? '#fff' : '#333',
                        padding: 20,
                        usePointStyle: true,
                        font: { size: 12, weight: 'bold' }
                    }
                }
            }
        }
    });

    const observer = new MutationObserver(() => {
        const isDarkNow = body.classList.contains('dark-mode') || body.classList.contains('dark');
        chart.options.plugins.legend.labels.color = isDarkNow ? '#fff' : '#333';
        chart.update();
    });
    observer.observe(body, { attributes: true, attributeFilter: ['class'] });
});
</script>