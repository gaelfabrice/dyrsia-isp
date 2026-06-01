<style>

/* ১. মেইন কন্টেন্ট এরিয়ার প্যাডিং কমানোর জন্য */
.content-wrapper, .content {
    padding-top: 0 !important;
}

/* ২. টাইটেল বা হেডার সেকশনের মার্জিন কমানোর জন্য */
.content-header {
    padding: 5px 15px 0 15px !important;
    margin-bottom: -10px !important;
}

/* ৩. আপনার ড্যাশবোর্ড রো-এর জন্য স্পেশাল ফিক্স */
.dashboard-row {
    display: flex !important;
    flex-wrap: wrap !important;
    margin-top: -15px !important; /* এটি বক্সগুলোকে টাইটেলের দিকে টেনে তুলবে */
    padding-top: 0 !important;
}

/* ৪. টাইটেল টেক্সট এর গ্যাপ কমানো */
.content-header h1, .content-header h2 {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    padding-bottom: 25px !important;
}

/* ===== Card Layout ===== */
.dashboard-card {
    background: var(--card-bg, #ffffff);
    border-radius: 16px;
    padding: 12px 14px;
    position: relative;
    height: 95px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    text-decoration: none;
    overflow: hidden; /* ðŸ”¥ strip curve fix */
    transition: all 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

/* ===== Left Strip (CURVED PERFECT) ===== */
.left-strip {
    width: 5px;
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    border-radius: 16px 0 0 16px;
    transition: all 0.3s ease;
}

/* ===== TOP ROW ===== */
.card-top {
    display: flex;
    align-items: center;
    gap: 0px;
}

/* ===== ICON ===== */
.card-icon {
    width: 30px;
    height: 30px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 25px;
    transition: all 0.3s ease;
}

/* ===== TITLE ===== */
.card-text small {
    font-size: 13px;
    font-weight: 600;
    color: var(--card-small-text, #777);
}

/* ===== BDT SMALL ===== */
.card-bdt {
    font-size: 15px;
    color: var(--card-bdt, #999);
    margin-left: 4px;
    margin-top: 0px;
}

/* ===== BIG NUMBER ===== */
.card-text h4 {
    margin: 0;
    margin-left: 10px;
    font-size: 30px;
    font-weight: 700;
    color: var(--card-number, #111);
    line-height: 1;
    transition: all 0.3s ease;
}

/* ===== LIVE DOT ===== */
.live-dot {
    width: 8px;
    height: 8px;
    background: #00e676;
    border-radius: 50%;
    position: absolute;
    right: 12px;
    top: 12px;
}

/* ===== Dashboard Grid ===== */
.dashboard-row {
    display: flex;
    flex-wrap: wrap;
    margin: -7px;
}

.dashboard-col {
    width: 50%;
    padding: 7px;
    box-sizing: border-box;
}

@media (min-width: 768px) {
    .dashboard-col { width: 33.33%; }
}

@media (min-width: 1200px) {
    .dashboard-col { width: 25%; }
}

/* ===== Card Layout (Light Mode Default) ===== */
.dashboard-card {
    background: #ffffff; /* লাইট মোডে সাদা */
    border-radius: 16px;
    padding: 12px 14px;
    position: relative;
    height: 95px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    text-decoration: none;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

/* আইকন বক্স (ব্যাকগ্রাউন্ড ছাড়া) */
.card-icon {
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 25px;
    background: transparent !important; /* ব্যাকগ্রাউন্ড থাকবে না */
}

.card-top {
    display: flex;
    align-items: center;
}

.card-text h4 {
    margin: 0 0 0 10px;
    font-size: 28px;
    font-weight: 700;
    color: #111;
}

/* ===== DARK MODE (এখানেই মূল ফিক্স) ===== */
body.dark-mode {
    background-color: #0f0f1a; /* পুরো পেজের ব্যাকগ্রাউন্ড */
}

/* ডার্ক মোডে ৪টি বক্স/কার্ড ডার্ক করা */
body.dark-mode .dashboard-card {
    background: #1e1e2f !important; /* কার্ড এখন ডার্ক হবেই */
    box-shadow: 0 4px 15px rgba(0,0,0,0.4);
}

/* ডার্ক মোডে আইকন ব্যাকগ্রাউন্ড রিমুভ */
body.dark-mode .card-icon {
    background: transparent !important;
    box-shadow: none !important;
}

/* ডার্ক মোডে আইকন কালার ব্রাইট করা */
body.dark-mode .card-icon i,
body.dark-mode .card-icon span {
    filter: brightness(1.3) saturate(1.2);
}

/* ডার্ক মোডে টেক্সট সাদা করা */
body.dark-mode .card-text h4 {
    color: #ffffff !important;
}

body.dark-mode .card-text small {
    color: #aaaaaa !important;
}

body.dark-mode .card-bdt {
    color: #bbbbbb !important;
}

/* লেফট স্ট্রিপ এর জন্য */
.left-strip {
    width: 5px;
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    border-radius: 16px 0 0 16px;
}
/* ===== আইকন সাইজ বড় করার জন্য আপডেট ===== */
.card-icon {
    width: 45px;      /* আইকন বক্সের এরিয়া বাড়ানো হয়েছে */
    height: 45px;
    font-size: 35px;   /* আইকনটি বড় করার মূল লাইন (আগে ২৫ ছিল) */
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent !important;
    transition: transform 0.3s ease;
}

/* মাউস নিলে আইকনটি সামান্য বড় হওয়ার ইফেক্ট (ঐচ্ছিক) */
.dashboard-card:hover .card-icon {
    transform: scale(1.1);
}

/* ডার্ক মোডে আইকন যাতে আরও স্পষ্ট থাকে */
body.dark-mode .card-icon {
    filter: brightness(1.3);
}
/* ===== DARK MODE (60% Dark + 40% Blue) ===== */
body.dark-mode {
    background-color: #0d0f14; /* পুরো বডির জন্য গাঢ় ডার্ক */
}

/* কার্ডের জন্য ব্যালেন্সড ডার্ক ব্লু শেড */
body.dark-mode .dashboard-card {
    background: #161b2e !important; /* ৬০% ডার্কনেস এবং ৪০% ব্লু এর মিশ্রণ */
    border: 1px solid #232a42;      /* হালকা নীলচে ডার্ক বর্ডার */
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
}

/* আইকন সেটিংস (বড় সাইজ এবং ব্যাকগ্রাউন্ড ছাড়া) */
body.dark-mode .card-icon {
    background: transparent !important;
    font-size: 38px;
    box-shadow: none !important;
}

/* ডার্ক মোডে আইকন ব্রাইটনেস */
body.dark-mode .card-icon i,
body.dark-mode .card-icon span {
    filter: brightness(1.4) saturate(1.3);
}

/* টেক্সট কালার */
body.dark-mode .card-text h4 {
    color: #ffffff !important; 
}

body.dark-mode .card-text small {
    color: #94a3b8 !important; /* হালকা নীলচে গ্রে টেক্সট */
}

body.dark-mode .card-bdt {
    color: #64748b !important;
}

/* হোভার ইফেক্ট */
body.dark-mode .dashboard-card:hover {
    background: #1c233a !important; /* হোভার করলে নীল ভাব সামান্য বাড়বে */
    border-color: #3b82f6; 
}
/* ===== ১. সব থিমের জন্য কমন আইকন সাইজ (হোয়াইট + ডার্ক) ===== */
.card-icon {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 38px !important; /* হোয়াইট থিমেও এখন আইকন বড় দেখাবে */
    background: transparent !important;
    box-shadow: none !important;
    transition: all 0.3s ease;
}

/* ===== ২. ডার্ক মোড (৬০% ডার্ক + ৪০% ব্লু) সেকশন আগের মতোই ===== */
body.dark-mode {
    background-color: #0d0f14;
}

body.dark-mode .dashboard-card {
    background: #161b2e !important;
    border: 1px solid #232a42;
}

/* ডার্ক মোডে শুধু ব্রাইটনেস অ্যাডজাস্ট হবে, সাইজ কমন কোড থেকে পাবে */
body.dark-mode .card-icon {
    filter: brightness(1.4) saturate(1.3);
}

/* টেক্সট এবং বাকি ডিজাইন */
body.dark-mode .card-text h4 { color: #ffffff !important; }
body.dark-mode .card-text small { color: #94a3b8 !important; }

/* ===== হোয়াইট থিম কার্ড ডিজাইন (হোভার ফিক্সড) ===== */
.dashboard-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 12px 16px;
    position: relative;
    height: 95px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    overflow: hidden; 
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.08);
    /* মেইন স্ট্রিপের কার্ভড লুক */
    box-shadow: inset 4px 0 0 #ff2e93; 
}

/* স্থায়ী কার্ভড স্ট্রিপ */
.left-strip {
    position: absolute;
    left: 0;
    top: 15%;
    width: 4px;
    height: 70%;
    background: linear-gradient(180deg, #ff2e93, #3b82f6);
    border-radius: 0 10px 10px 0;
    box-shadow: inset 1px 1px 3px rgba(0,0,0,0.3);
    z-index: 10; /* এটি নিশ্চিত করবে স্ট্রিপ সব সময় উপরে থাকবে */
}

/* হোয়াইট থিম হোভার ফিক্স (যাতে স্ট্রিপ চলে না যায়) */
.dashboard-card:hover {
    transform: translateY(-3px);
    background: #ffffff !important; /* ব্যাকগ্রাউন্ড সাদা থাকবে */
    border-color: rgba(0,0,0,0.15);
    /* হোভার করলেও ইনসেট স্ট্রিপ শ্যাডো বজায় থাকবে */
    box-shadow: inset 4px 0 0 #ff2e93, 0 8px 20px rgba(0,0,0,0.08);
}

/* ===== ডার্ক থিম (একদম পারফেক্ট কার্ভড লুক) ===== */
body.dark-mode .dashboard-card {
    background: #161b2e !important;
    border: 1px solid #232a42;
    box-shadow: inset 4px 0 0 #ff2e93, 
                inset 0 0 15px rgba(0,0,0,0.5), 
                0 10px 20px rgba(0,0,0,0.3);
}

body.dark-mode .dashboard-card:hover {
    background: #1c233a !important;
    box-shadow: inset 4px 0 0 #ff2e93, 0 12px 25px rgba(0,0,0,0.4);
}
/* ===== ১. ড্যাশবোর্ড গ্রিড (পারফেক্ট গ্যাপ কন্ট্রোল) ===== */
.dashboard-row {
    display: flex !important;
    flex-wrap: wrap !important;
    margin: 0 -4px !important; /* স্ক্রিনের সাথে এলাইন রাখার জন্য */
    padding: 0 !important;
}

.dashboard-col {
    position: relative !important;
    /* বক্সের সাইজ ফিক্সড (৫০%) */
    width: 50% !important; 
    /* পাশাপাশি গ্যাপ কমানোর জন্য খুবই সামান্য প্যাডিং */
    padding: 2px !important; 
    box-sizing: border-box !important;
    
    /* ১ম লাইন আর ২য় লাইনের মাঝখানের গ্যাপ বড় করার জন্য */
    margin-bottom: 12px !important; 
    margin-top: 0 !important;
}

@media (min-width: 768px) {
    .dashboard-col { 
        width: 25% !important; 
        padding: 3px !important; /* ডেস্কটপে ৪টি বক্সের গ্যাপ */
        margin-bottom: 15px !important; 
    }
}

/* ===== ২. কার্ড ডিজাইন (বক্স সাইজ যাতে ছোট না হয়) ===== */
.dashboard-card {
    height: 85px; 
    width: 100% !important; /* কলামের পুরো জায়গা নেবে */
    background: #ffffff;
    border-radius: 14px; 
    padding: 10px 14px;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.06);
    /* আপনার সেই সিগনেচার কার্ভড স্ট্রিপ */
    box-shadow: inset 4px 0 0 #ff2e93; 
}

/* কালার স্ট্রিপ */
.left-strip {
    z-index: 10;
    position: absolute;
    left: 0;
    top: 15%;
    width: 4px;
    height: 70%;
    background: linear-gradient(180deg, #ff2e93, #3b82f6);
    border-radius: 0 10px 10px 0;
}

/* ডার্ক মোড ফিক্স */
body.dark-mode .dashboard-card {
    background: #161b2e !important;
    border: 1px solid #232a42;
    box-shadow: inset 4px 0 0 #ff2e93;
}
</style>

<?php
$pppoe_online   = $pppoeOnline ?? 0;
$hotspot_online = $hotspotOnline ?? 0;
?>

<div class="dashboard-row">
    
<!-- Total Administrator Box -->
<div class="dashboard-col">
    <!-- আপনার দেয়া রাউট অনুযায়ী লিঙ্ক -->
    <a href="index.php?_route=settings/users" class="dashboard-card">

    <!-- নীল রঙ বা আপনার পছন্দমতো গ্রেডিয়েন্ট -->
    <div class="left-strip" style="background: linear-gradient(to bottom, #3f51b5, #5c6bc0);"></div>

    <div class="card-top">
        <!-- ইউজার আইকন -->
        <div class="card-icon" style="background:#e8eaf6;color:#3f51b5;">
            <i class="ion ion-android-people"></i>
        </div>
        <div class="card-text">
            <small>TOTAL ADMINISTRATOR</small>
        </div>
    </div>

    <!-- সাব-টাইটেল বা লেবেল -->
    <div class="card-bdt">RESELLERS</div>

    <div class="card-text">
        <!-- এখানে ডাটাবেজ থেকে আসা এডমিন সংখ্যা বসবে -->
        <h4>{$staff_count|default:"0"}</h4>
    </div>

    </a>
</div>

<!-- Active / Expired -->
<div class="dashboard-col">
<a href="{Text::url('plan/list')}" class="dashboard-card">

<div class="left-strip" style="background: linear-gradient(to bottom, #ff9800, #ffc947);"></div>

<div class="card-top">
    <div class="card-icon" style="background:#fff3e0;color:#ff9800;">
        <i class="ion ion-person"></i>
    </div>
    <div class="card-text"><small>ACTIVE / EXPIRED</small></div>
</div>

<div class="card-text">
    <h4 style="font-size:30px;">
        <span style="color:#00c853;">{$u_act}</span>
        <span style="color:#f44336;"> / {$u_all-$u_act}</span>
    </h4>
</div>

</a>
</div>

<!-- Hotspot Active -->
<div class="dashboard-col">
   <a href="index.php?_route=plan/list&type=Hotspot&status=On" class="dashboard-card">
        <div class="left-strip" style="background: linear-gradient(to bottom, #009688, #4db6ac);"></div>
        <div class="card-top">
            <div class="card-icon" style="background:#e0f2f1;color:#009688;">
                <i class="ion ion-ios-checkmark"></i>
            </div>
            <div class="card-text"><small>HOTSPOT ACTIVE</small></div>
        </div>
        <div class="card-text">
            <h4>{$h_act}</h4>
        </div>
    </a>
</div>

<!-- Hotspot Expired -->
<div class="dashboard-col">
    <a href="index.php?_route=plan/list&type=Hotspot&status=Off" class="dashboard-card">
        <div class="left-strip" style="background: linear-gradient(to bottom, #e91e63, #f06292);"></div>
        <div class="card-top">
            <div class="card-icon" style="background:#fce4ec;color:#e91e63;">
                <i class="ion ion-close-circled"></i>
            </div>
            <div class="card-text"><small>HOTSPOT EXPIRED</small></div>
        </div>
        <div class="card-text">
            <h4>{$h_exp}</h4>
        </div>
    </a>
</div>

<!-- PPPoE Active -->
<div class="dashboard-col">
    <a href="index.php?_route=plan/list&type=PPPoE&status=On" class="dashboard-card">
        <div class="left-strip" style="background: linear-gradient(to bottom, #607d8b, #90a4ae);"></div>
        <div class="card-top">
            <div class="card-icon" style="background:#eceff1;color:#607d8b;">
                <i class="ion ion-ios-pulse-strong"></i>
            </div>
            <div class="card-text"><small>PPPOE ACTIVE</small></div>
        </div>
        <div class="card-text">
            <h4>{$p_act}</h4>
        </div>
    </a>
</div>

<!-- PPPoE Expired -->
<div class="dashboard-col">
    <a href="index.php?_route=plan/list&type=PPPoE&status=Off" class="dashboard-card">
        <div class="left-strip" style="background: linear-gradient(to bottom, #795548, #a1887f);"></div>
        <div class="card-top">
            <div class="card-icon" style="background:#efebe9;color:#795548;">
                <i class="ion ion-alert-circled"></i>
            </div>
            <div class="card-text"><small>PPPOE EXPIRED</small></div>
        </div>
        <div class="card-text">
            <h4>{$p_exp}</h4>
        </div>
    </a>
</div>

</div>
<script>
function animateValue(id, start, end, duration) {
    let obj = document.getElementById(id);
    if (!obj) return;
    let range = end - start;
    let startTime = null;

    function step(timestamp) {
        if (!startTime) startTime = timestamp;
        let progress = timestamp - startTime;
        let percent = Math.min(progress / duration, 1);
        obj.innerText = Math.floor(start + range * percent);
        if (percent < 1) requestAnimationFrame(step);
    }

    requestAnimationFrame(step);
}

function updateLiveBoxes() {
    ['pppoe','hotspot'].forEach(type => {
        // âœ… Correct backend URL for each service
        let url = type === 'pppoe'
                    ? 'index.php?_route=plugin/pppoe_online_ui&ajax=1'
                    : 'index.php?_route=plugin/hotspot_online_ui&ajax=1';

        fetch(url)
        .then(resp => resp.json())
        .then(data => {
            const count = Array.isArray(data) ? data.length : 0;
            let boxId = type === 'pppoe' ? 'pppoeBox' : 'hotspotBox';
            let box = document.getElementById(boxId);
            if (!box) return;
            let current = parseInt(box.innerText) || 0;
            animateValue(boxId, current, count, 500);
        })
        .catch(() => {
            let pppoeBox = document.getElementById("pppoeBox");
            let hotspotBox = document.getElementById("hotspotBox");
            if (pppoeBox) pppoeBox.innerText = 0;
            if (hotspotBox) hotspotBox.innerText = 0;
        });
    });
}

// Run on page load
document.addEventListener("DOMContentLoaded", updateLiveBoxes);

// Refresh every 5 seconds
setInterval(updateLiveBoxes, 5000);
</script>