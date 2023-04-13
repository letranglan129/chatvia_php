<?php

use App\Libraries\Session;
?>
<?= $this->extend('layouts/default.php') ?>
<?= $this->section('content') ?>
<div class="chat d-flex flex-row h-100 d-none">
    <!-- Chat -->
    <div class="chat-body h-100 w-100 d-flex flex-column">
        <!-- Chat Header -->
        <div class="chat-header d-flex align-items-center border-bottom px-2">
            <div class="container-fluid">
                <div class="row align-items-center g-0">
                    <div class="col-8 col-sm-5">
                        <div class="d-flex align-items-center user-card user-chat-main-header">
                            <!-- Close Chat Button -->
                            <div class="d-block d-xl-none me-3">
                                <button class="chat-hide btn btn-icon btn-base btn-sm" type="button">
                                    <i class="ri-arrow-left-s-line"></i>
                                </button>
                            </div>
                            <!-- Close Chat Button -->

                            <!-- Avatar -->
                            <div class="avatar avatar-sm me-3" id="chat-header-avatar">
                                <span class="avatar-label bg-soft-primary text-primary fs-6">AM</span>
                            </div>
                            <!-- Avatar -->

                            <!-- Text -->
                            <div class="flex-grow-1 overflow-hidden">
                                <h6 class="d-block text-truncate mb-1" id="name-group"></h6>
                            </div>
                            <!-- Text -->
                        </div>
                    </div>
                    <div class="col-4 col-sm-7">
                        <ul class="list-inline text-end mb-0">

                            <!-- Chat Info Button -->
                            <li class="list-inline-item d-none d-sm-inline-block">
                                <button class="chat-info-toggle btn btn-icon btn-base" title="Chat info" type="button">
                                    <i class="ri-information-fill"></i>
                                </button>
                            </li>
                            <!-- Chat Info Button -->

                            <!-- Dropdown -->
                            <li class="list-inline-item">
                                <div class="dropdown">
                                    <button class="btn btn-icon btn-base" type="button" title="Menu" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-more-fill"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li class="d-block d-sm-none">
                                            <a class="chat-info-toggle dropdown-item d-flex align-items-center justify-content-between">
                                                Thông tin
                                                <i class="ri-information-line"></i>
                                            </a>
                                        </li>
                                        <li id="menu-dropdown-conversation">

                                        </li>
                                    </ul>
                                </div>
                            </li>
                            <!-- Dropdown -->
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- Chat Header -->

        <!-- Chat Search -->
        <div>
            <div class="border-bottom collapse" id="search-chat">
                <div class="px-1 py-4">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col">
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-lg" placeholder="Search in chat" aria-label="Search in chat" aria-describedby="search-in-chat-button">
                                    <button class="btn btn-white btn-lg border" type="button" id="search-in-chat-button"><i class="ri-search-line"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Chat Search -->

        <!-- Chat Content -->
        <div class="chat-content h-100 overflow-auto">
            <!-- Messages -->
            <div class="container-fluid g-0 p-4">
               
            </div>
            <!-- Messages -->

            <!-- Scroll Chat to Bottom -->
            <div class="js-scroll-to-bottom"></div>
            <!-- Scroll Chat to Bottom -->
        </div>
        <!-- Chat Content -->

        <!-- Chat Footer -->
        <div class="chat-footer d-flex align-items-center border-top px-2 py-4" style="min-height: unset;">
            <div class="container-fluid">
                <form class="row align-items-center g-4" name="message-form">
                    <!-- Input -->
                    <div class="col">
                        <div class="input-group">
                            <label class="btn btn-white btn-lg border" for="images-upload"><i class="ri-attachment-2"></i></label>
                            <input type="file" name="images-upload[]" id="images-upload" multiple hidden>
                            <div contenteditable="true" id="msg-input" class="form-control form-control-lg" style="
                            overflow: auto;
                            min-height: 24px;
                            max-height: 124px;
                            font-size: 15px;
                            line-height: 24px;
                        "></div>
                            <!-- <input type="text" class="" name="msg" placeholder="Type message" aria-label="type message"> -->
                            <button class="btn btn-white btn-lg border" id="emoji-btn" type="button"><i class="ri-chat-smile-2-line"></i></button>
                        </div>
                    </div>
                    <!-- Input -->

                    <!-- Button -->
                    <div class="col-auto">
                        <ul class="list-inline d-flex align-items-center mb-0">
                            <li class="list-inline-item">
                                <button type="submit" class="btn btn-icon btn-primary btn-lg rounded-circle">
                                    <i class="ri-send-plane-fill"></i>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <!-- Button -->
                </form>
            </div>
        </div>
        <!-- Chat Footer -->
    </div>
    <!-- Chat -->

    <!-- Chat Info -->
    <div class="chat-info h-100 border-start">
        <div class="d-flex flex-column h-100">
            <!-- Header -->
            <div class="chat-info-header d-flex align-items-center border-bottom">
                <ul class="d-flex justify-content-between align-items-center list-unstyled w-100 mx-4 mb-0">
                    <!-- Title -->
                    <li>
                        <h3 class="mb-0">Thông tin cuộc trò chuyện</h3>
                    </li>
                    <!-- Title -->

                    <!-- Close Button -->
                    <li>
                        <button class="chat-info-close btn btn-icon btn-base px-0">
                            <i class="ri-close-line"></i>
                        </button>
                    </li>
                    <!-- Close Button -->
                </ul>
            </div>
            <!-- Header -->

            <!-- Content -->
            <div class="hide-scrollbar h-100" id="chatInfoWrap">
                <!-- User Info -->
                <div class="text-center p-4 pt-14">
                    <!-- Avatar -->
                    <div class="avatar avatar-xl mb-4">
                        <span class="avatar-label bg-soft-primary text-primary fs-3">AM</span>
                    </div>
                    <!-- Avatar -->

                    <!-- Text -->
                    <h5>Ariel Martinez</h5>
                    <!-- Text -->

                    <!-- Text -->
                    <p class="text-muted fs-6">UX/UI Design</p>
                    <!-- Text -->

                    <!-- Text -->
                    <div class="text-center">
                        <span class="text-muted mb-0">Graphic designer.<br>
                            Working with landing pages and templates.</span>
                    </div>
                    <!-- Text -->
                </div>
                <!-- User Info -->

                <!-- Segmented Control -->
                <div class="text-center mb-2">
                    <ul class="nav nav-pills nav-segmented" id="pills-tab-user-profile" role="tablist">
                        <!-- About -->
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-about-tab" data-bs-toggle="pill" data-bs-target="#pills-about" type="button" role="tab" aria-controls="pills-about" aria-selected="true">About</button>
                        </li>
                        <!-- About -->

                        <!-- Files -->
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-files-tab" data-bs-toggle="pill" data-bs-target="#pills-files" type="button" role="tab" aria-controls="pills-files" aria-selected="false">Files</button>
                        </li>
                        <!-- Files -->
                    </ul>
                </div>
                <!-- Segmented Control -->

                <!-- Tab Content -->
                <div class="tab-content" id="pills-tab-user-profile-content">
                    <!-- About -->
                    <div class="tab-pane fade show active" id="pills-about" role="tabpanel" aria-labelledby="pills-about-tab">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item py-4">
                                <h6 class="mb-1">Name</h6>
                                <p class="text-truncate mb-0">Ariel Martinez</p>
                            </li>
                            <li class="list-group-item py-4">
                                <h6 class="mb-1">Email</h6>
                                <p class="text-truncate mb-0">ariel@gmail.com</p>
                            </li>
                            <li class="list-group-item py-4">
                                <h6 class="mb-1">Phone</h6>
                                <p class="text-truncate mb-0">646-210-1784</p>
                            </li>
                            <li class="list-group-item py-4">
                                <h6 class="mb-1">Location</h6>
                                <p class="text-truncate mb-0">New York, USA</p>
                            </li>
                        </ul>
                    </div>
                    <!-- About -->

                    <!-- Files -->
                    <div class="tab-pane fade" id="pills-files" role="tabpanel" aria-labelledby="pills-files-tab">
                        <ul class="list-group list-group-flush">
                            
                        </ul>
                    </div>
                    <!-- Files -->
                </div>
                <!-- Tab Content -->
            </div>
            <!-- Content -->
        </div>
    </div>
    <!-- Chat Info -->
</div>

<div class="welcome d-xl-flex flex-column h-100 align-items-center justify-content-center">
    <div style="padding: 0 100px;" class="text-center">
        <h2>Chào mừng bạn đến với ChatVia</h2>
        <p style="font-size: 14px;">Khám phá những tiện ích hỗ trợ làm việc và trò chuyện cùng người thân, bạn bè được tối ưu hoá cho máy tính của bạn.</p>

    </div>
    <div class="swiper w-100">
        <!-- Additional required wrapper -->
        <div class="swiper-wrapper">
            <!-- Slides -->
            <div class="swiper-slide text-center py-4">
                <img src="./img/banner1.png" alt="" height="230">
                <p>Nhắn tin nhiều hơn, soạn thảo ít hơn</p>
            </div>
            <div class="swiper-slide text-center py-5">
                <img src="./img/banner2.png" alt="" height="230">
                <p>
                    Trải nghiệm xuyên suốt
                </p>
            </div>
        </div>
        <!-- If we need pagination -->
        <div class="swiper-pagination"></div>
    </div>
</div>
<?= $this->endSection() ?>