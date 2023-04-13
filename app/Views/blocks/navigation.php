<?php

use App\Libraries\Session;
use App\Libraries\Ultis;
?>

<div class="navigation navbar border-end h-100 py-6">
    <!-- Logo -->
    <a class="logo d-none d-xl-block mb-6 w-100" href="#">
        <img src="./img/logo_noname.svg" class=" w-100 p-5" alt="">
    </a>
    <!-- Logo -->

    <!-- Nav -->
    <ul class="nav nav-pills" role="tablist" id="nav-list">
        <!-- Chats -->
        <li class="nav-item mt-xl-auto mb-5 nav-chat" role="presentation">
            <a class="nav-link active" data-bs-toggle="pill" href="#chats-tab" role="tab" aria-selected="true" title="Trò chuyện">
                <i class="ri-chat-3-line"></i>
            </a>
            <span class="dot d-none"></span>
        </li>
        <!-- Chats -->

        <!-- Create chat -->
        <li class="nav-item  mb-5" role="presentation">
            <a class="nav-link" data-bs-toggle="pill" href="#create-chat-tab" role="tab" aria-selected="false" title="Tạo cuộc trò chuyện">
                <i class="ri-quill-pen-line"></i>
            </a>
        </li>
        <!-- Create chat -->

        <!-- Friends -->
        <li class="nav-item mb-5" role="presentation">
            <a class="nav-link" data-bs-toggle="pill" href="#friends-tab" role="tab" aria-selected="false" title="Bạn bè">
                <i class="ri-group-line"></i>
            </a>
        </li>
        <!-- Friends -->

        <!-- Notifications -->
        <li class="nav-item mb-5 nav-notify" role="presentation">
            <a class="nav-link" data-bs-toggle="pill" href="#notifications-tab" role="tab" aria-selected="false" title="Thông báo">
                <i class="ri-notification-3-line"></i>
            </a>
            <span class="dot d-none"></span>
        </li>
        <!-- Notifications -->

        <!-- Settings -->
        <li class="nav-item mb-5" role="presentation">
            <a class="nav-link" data-bs-toggle="pill" href="#settings-tab" role="tab" aria-selected="false" title="Cài đặt">
                <i class="ri-settings-4-line"></i>
            </a>
        </li>
        <!-- Settings -->

        <!-- Avatar -->
        <li class="nav-item mt-xl-auto mb-5" role="presentation">
            <a class="text-decoration-none" onclick="showAccountModal(<?php echo Session::data('user')['id'] ?>)" title="Account">
                <div class="avatar avatar-online avatar-sm avatar-owner">
                    <?php
                    if (isset(Session::data('user')['avatar'])) {
                        echo ("<img src='" . Session::data('user')['avatar'] . "' alt='' >");
                    } else {
                        echo ("<span class='avatar-label bg-soft-success text-success fs-6'>" . Ultis::compactName(Session::data('user')['fullname']) . "</span>");
                    }
                    ?>
                </div>
            </a>
        </li>
        <!-- Avatar -->
    </ul>
    <!-- Nav -->
</div>