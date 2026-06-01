<div class="box box-solid theme-box exceptional-box">
    <div class="box-header">
        <div class="header-icon">
            <i class="fa fa-line-chart"></i>
        </div>
        <h3 class="box-title">{Lang::T('Monthly Registered Customers')}</h3>
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
            <canvas class="chart" id="chart" style="height: 250px;"></canvas>
        </div>
    </div>
</div>

<style>
/* ==========================================
   STRIP & BOX DESIGN (Pink & Blue)
========================================== */
.exceptional-box {
    background: #ffffff !important;
    color: #333;
    border-radius: 14px !important;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden !important; 
    border: 1px solid rgba(0,0,0,0.06) !important;
    /* ওপরের পিঙ্ক-ব্লু ইনসেট লুক */
    box-shadow: inset 0 4px 0 #ff2e93, 0 6px 15px rgba(0,0,0,0.05) !important;
}

/* ওপরের স্ট্রিপ (Pink to Blue) */
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
   BUTTONS (Green Icon Color)
========================================== */
.btn-icon-only {
    background: transparent !important;
    border: none !important;
    box-sizing: border-box !important;
    box-shadow: none !important;
    /* আপনার চাওয়া অনুযায়ী সবুজ রঙ */
    color: #00d084 !important; 
    font-size: 16px !important;
    padding: 5px !important;
    transition: all 0.2s ease-in-out;
    cursor: pointer;
}

.btn-icon-only:hover {
    transform: scale(1.2);
    /* হোভারে সামান্য উজ্জ্বল সবুজ */
    color: #00ffa2 !important; 
    opacity: 1;
}

/* আইকনগুলোর মাঝখানে সামান্য গ্যাপের জন্য */
.box-tools .btn-icon-only {
    margin-left: 5px;
}
/* ==========================================
   HEADER ICON (Extra Large & Clean Yellow)
========================================== */
.exceptional-box .header-icon, 
.exceptional-box .header-icon i,
.exceptional-box .box-header i.fa-bar-chart,
.exceptional-box .box-header i.fa-line-chart,
.exceptional-box .box-header i.fa-th {
    /* সাইজ আরও বড় করা হলো */
    font-size: 30px !important; 
    color: #FFD700 !important;
    display: inline-block !important;
    
    /* একদম ক্লিন লুক */
    text-shadow: none !important;
    filter: none !important;
    box-shadow: none !important;
    
    vertical-align: middle;
    margin-right: 5px;
    -webkit-text-fill-color: #FFD700 !important;
}

/* টাইটেল যাতে বড় আইকনের সাথে ঠিকমতো এলাইন থাকে */
.exceptional-box .box-header .box-title {
    vertical-align: middle;
    font-size: 18px !important;
    display: inline-block;
}

</style>

<script type="text/javascript">
{literal}
document.addEventListener("DOMContentLoaded", function() {
    var counts = JSON.parse('{/literal}{$monthlyRegistered|json_encode}{literal}');
    var monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    var labels = [], data = [];
    for (var i = 1; i <= 12; i++) {
        var month = counts.find(count => count.date === i);
        labels.push(monthNames[i-1]);
        data.push(month ? month.count : 0);
    }

    var ctx = document.getElementById('chart').getContext('2d');
    
    // থিম ডিটেকশন
    var isDark = document.body.classList.contains('dark-mode') || document.body.classList.contains('dark');

    // হোয়াইট থিমে দাগ (Grid Line) স্পষ্ট করার জন্য কালার সেট
    var gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.12)'; 
    var textColor = isDark ? '#a0aec0' : '#555555';

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
                label: 'Registered',
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
                    grid: { display: false }, // নিচের দাগ অফ থাকবে ক্লিন দেখানোর জন্য
                    ticks: { color: textColor, font: { weight: '500' } }
                },
                y: {
                    beginAtZero: true,
                    grid: { 
                        color: gridColor, // এখানে দাগগুলো এখন স্পষ্ট হবে
                        drawBorder: false 
                    },
                    ticks: { 
                        color: textColor, 
                        stepSize: 1,
                        font: { weight: '500' }
                    }
                }
            }
        }
    });

    // অটো আপডেট (থিম চেঞ্জ হলে)
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