<?php

_admin();

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (!empty($query)) {
    // এখানে where_any_is ব্যবহার করে একাধিক কলামে সার্চ করা হয়েছে
    $results = ORM::for_table('tbl_customers')
        ->where_raw('(`username` LIKE ? OR `fullname` LIKE ? OR `phonenumber` LIKE ?)', array("%$query%", "%$query%", "%$query%"))
        ->find_many();

    if ($results) {
        echo '<ul class="list-group">'; // দেখতে সুন্দর করার জন্য বুটস্ট্র্যাপ ক্লাস যোগ করতে পারেন
        foreach ($results as $user) {
            // রেজাল্টে ইউজারনেমের পাশাপাশি নাম এবং ফোন নাম্বারও দেখালে ইউজারদের চিনতে সুবিধা হবে
            echo '<li>';
            echo '<a href="'.$_url.'?_route=customers/view/'.$user->id.'">';
            echo '<strong>' . htmlspecialchars($user->username, ENT_QUOTES, 'UTF-8') . '</strong>';
            echo ' - ' . htmlspecialchars($user->fullname, ENT_QUOTES, 'UTF-8');
            echo ' (' . htmlspecialchars($user->phonenumber, ENT_QUOTES, 'UTF-8') . ')';
            echo '</a>';
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>' . Lang::T('No users found.') . '</p>';
    }
} else {
    echo '<p>' . Lang::T('Please enter a search term.') . '</p>';
}