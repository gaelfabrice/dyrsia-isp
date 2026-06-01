<div class="box box-solid theme-box-sales exceptional-box">
    <div class="box-header">
        <div class="header-icon">
            <i class="fa fa-inbox"></i>
        </div>
        <h3 class="box-title">{Lang::T('Total Monthly Sales')}</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-sm btn-icon-only" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
            <a href="{Text::url('dashboard&refresh')}" class="btn btn-sm btn-icon-only">
                <i class="fa fa-refresh"></i>
            </a>
        </div>
    </div>
    <div class="box-body no-padding">
        <div class="chart-container" style="padding: 10px 15px;">
            <canvas class="chart" id="salesChart" style="height: 250px;"></canvas>
        </div>
    </div>
</div>

<style>
/* ==========================================
   EXCEPTIONAL BOX - REDUCED GAP
========================================== */
.exceptional-box {
    background: #ffffff !important;
    color: #333;
    border-radius: 14px !important;
    
    /* বক্সের মাঝখানের গ্যাপ কমানো হলো */
    margin-bottom: 6px !important; 
    
    position: relative;
    overflow: hidden !important; 
    border: 1px solid rgba(0,0,0,0.06) !important;
    box-shadow: inset 0 4px 0 #ff2e93, 0 6px 15px rgba(0,0,0,0.05) !important;
    transition: all 0.3s ease;
}

/* ওপরের সেই সিগনেচার স্ট্রিপ (Pink to Blue) */
.exceptional-box::before {
    content: "";
    position: absolute;
    left: 20%;     
    top: 0;
    width: 60%;    
    height: 4px;   
    background: linear-gradient(90deg, #ff2e93 0%, #3b82f6 100%) !important;
    border-radius: 0 0 10px 10px;
    z-index: 10;
}

/* ডার্ক মোড */
body.dark-mode .exceptional-box {
    background: #161b2e !important;
    color: #ffffff !important;
    border: 1px solid #232a42 !important;
    box-shadow: inset 0 4px 0 #ff2e93, 0 10px 25px rgba(0,0,0,0.3) !important;
}

/* ==========================================
   HEADER ICON (Big & Clean Yellow)
========================================== */
.exceptional-box .header-icon, 
.exceptional-box .header-icon i,
.exceptional-box .box-header i.fa-inbox {
    font-size: 30px !important; 
    color: #FFD700 !important;
    display: inline-block !important;
    text-shadow: none !important;
    filter: none !important;
    vertical-align: middle;
    margin-right: 5px;
    -webkit-text-fill-color: #FFD700 !important;
}

.exceptional-box .box-header .box-title {
    vertical-align: middle;
    font-weight: 700;
    font-size: 17px;
    margin: 0;
    display: inline-block;
}

/* ==========================================
   BUTTONS (Green & Transparent)
========================================== */
.btn-icon-only {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    color: #00d084 !important; /* সবুজ কালার */
    font-size: 16px !important;
    padding: 5px !important;
    transition: transform 0.2s;
}

.btn-icon-only:hover {
    transform: scale(1.2);
    color: #00ffa2 !important;
}

.exceptional-box .box-header {
    border-bottom: 1px solid rgba(0,0,0,0.03);
    padding: 15px 20px;
}
</style>

<script type="text/javascript">
{literal}
document.addEventListener("DOMContentLoaded", function() {
    var monthlySales = JSON.parse('{/literal}{$monthlySales|json_encode}{literal}');
    var monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    var labels = [], data = [];
    for (var i = 1; i <= 12; i++) {
        var month = findMonthData(monthlySales, i);
        labels.push(monthNames[i-1]);
        data.push(month ? month.totalSales : 0);
    }

    var ctx = document.getElementById('salesChart').getContext('2d');
    var isDark = document.body.classList.contains('dark-mode') || document.body.classList.contains('dark');

    // হোয়াইট থিমে দাগ স্পষ্ট করার জন্য কালার
    var gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.12)'; 
    var textColor = isDark ? '#a0aec0' : '#555555';

    // ১২ মাসের ১২টি আলাদা কালার
    var monthColors = [
        '#FF2E93', '#3B82F6', '#00D084', '#FF9100', 
        '#8B5CF6', '#EC4899', '#10B981', '#0EA5E9', 
        '#F59E0B', '#A855F7', '#14B8A6', '#FACC15'
    ];

    var chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales',
                data: data,
                backgroundColor: monthColors,
                borderRadius: 5,
                maxBarThickness: 25
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: textColor, font: { weight: '500' } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: textColor, font: { weight: '500' } }
                }
            }
        }
    });

    function findMonthData(monthlySales, month) {
        for (var i = 0; i < monthlySales.length; i++) {
            if (monthlySales[i].month === month) {
                return monthlySales[i];
            }
        }
        return null;
    }

    // অটো আপডেট থিম চেঞ্জ হলে
    const observer = new MutationObserver(() => {
        var isDarkNow = document.body.classList.contains('dark-mode') || document.body.classList.contains('dark');
        chart.options.scales.y.grid.color = isDarkNow ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.12)';
        chart.options.scales.x.ticks.color = isDarkNow ? '#a0aec0' : '#555555';
        chart.options.scales.y.ticks.color = isDarkNow ? '#a0aec0' : '#555555';
        chart.update();
    });
    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
});
{/literal}
</script>