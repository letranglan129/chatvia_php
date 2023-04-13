<?php

use App\Libraries\Session;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatflix &ndash; HTML Bootstrap 5 Chat Template</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />

    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js"></script>
    <script src="https://unpkg.com/popper.js@1"></script>
    <script src="https://unpkg.com/tippy.js@5"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@5/dist/backdrop.css" />
    <script src="./js/toast.js"></script>
    <link rel="stylesheet" href="./css/styles-light.min.css">
    <link rel="stylesheet" href="./css/styles-dark.min.css" media="(prefers-color-scheme: dark)">
    <link rel="stylesheet" href="./css/main.css">

</head>

<body>
    <!-- Layout -->
    <div class="layout">
        <!-- Navigation -->
        <?= $this->include('blocks/navigation') ?>
        <!-- Navigation -->

        <!-- Sidebar -->
        <div class="sidebar border-end overflow-hidden h-100">
            <div class="tab-content h-100">
                <!-- Create Chat Tab -->

                <?= $this->include('blocks/createChatTab') ?>
                <!-- Create Chat Tab -->

                <!-- Friends Tab -->

                <?= $this->include('blocks/friendTab') ?>
                <!-- Friends Tab -->

                <!-- Chats Tab -->

                <?= $this->include('blocks/chatTab',) ?>
                <!-- Chats Tab -->

                <!-- Notification Tab -->

                <?= $this->include('blocks/notificationTab') ?>
                <!-- Notification Tab -->

                <!-- Settings Tab -->

                <?= $this->include('blocks/settingTab') ?>
                <!-- Settings Tab -->
            </div>
        </div>
        <!-- Sidebar -->

        <!-- Main Content -->
        <div class="main main-visible overflow-hidden h-100">
            <?= $this->renderSection('content') ?>
        </div>
        <!-- Main Content -->
    </div>
    <!-- Layout -->
    <?= $this->include('blocks/accountModal') ?>

    <div class="modal fade" id="forward-message-modal" tabindex="-1" aria-labelledby="modal-account" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered forward">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="position-relative text-center w-100">
                        <div class="">
                            Chuyển tiếp
                        </div>

                        <button class="btn btn-icon bg-secondary position-absolute btn-sm rounded-circle" style="top: 50%;
                        right: 6px;
                        transform: translateY(-50%);" data-bs-dismiss="modal">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                </div>

                <div class="modal-body">
                    <form class="input-group">
                        <input type="search" class="form-control form-control-lg form-control-solid" placeholder="Tìm kiếm cuộc trò chuyện" aria-label="Search user" aria-describedby="search-user-button">
                        <button class="btn btn-secondary btn-lg" type="submit" id="search-user-button"><i class="ri-search-line"></i></button>
                    </form>
                    <form name="forwardMemberForm">

                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button id="submitForwardFormBtn" type="button" class="btn btn-primary" data-bs-dismiss="modal">Chia sẻ</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add-member-modal" tabindex="-1" aria-labelledby="modal-account" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered add-member">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="position-relative text-center w-100">
                        <div class="">
                            Thêm thành viên
                        </div>

                        <button class="btn btn-icon bg-secondary position-absolute btn-sm rounded-circle" style="top: 50%;
                        right: 6px;
                        transform: translateY(-50%);" data-bs-dismiss="modal">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                </div>

                <div class="modal-body">
                    <form class="input-group">
                        <input type="search" class="form-control form-control-lg form-control-solid" placeholder="Tìm kiếm cuộc trò chuyện" aria-label="Search user" aria-describedby="search-user-button">
                        <button class="btn btn-secondary btn-lg" type="submit" id="search-user-button"><i class="ri-search-line"></i></button>
                    </form>
                    <form name="addMemberForm">

                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button id="submitAddMemberFormBtn" type="button" class="btn btn-primary" data-bs-dismiss="modal">Thêm</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Scripts -->
    <script>
        const USER = JSON.parse('<?php echo (json_encode(Session::data('user'))) ?>')
    </script>
    <script src="https://cdn.jsdelivr.net/npm/emoji-mart@latest/dist/browser.js"></script>
    <script src="./js/filesize.js"></script>
    <script src="./js/app.min.js"></script>
    <script src="./js/main.js"></script>
    <!-- Scripts -->


</body>

</html>