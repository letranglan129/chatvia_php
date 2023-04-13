<?php

use App\Libraries\Session;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <title>Sign in to Chatflix</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css">
    <link rel="stylesheet" href="./css/styles-light.min.css">
    <link rel="stylesheet" href="./css/styles-dark.min.css" media="(prefers-color-scheme: dark)">
</head>

<body>
    <!-- Layout -->
    <div class="layout bg-light h-100">
        <div class="container h-100">
            <div class="row align-items-center justify-content-center g-0 h-100">
                <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
                    <!-- Heading -->
                    <div class="text-center mb-4">
                        <img src="./img/logo.png" height="40" alt="">
                    </div>
                    <h2 class="text-center mb-1">Đăng nhập <?php echo(Session::data('user') != null ? 17 : 0) ?></h2>
                    <!-- Heading -->

                    <!-- Text -->
                    <p class="text-center mb-4">Đăng nhập để sử dụng Chatvia.</p>
                    <!-- Text -->

                    <!-- Alert -->
                    <?php


                    if (Session::data('msg_reg_sucesss') != null) : ?>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="ri-checkbox-circle-fill" style="font-size: 24px; margin-right: 16px;"></i>
                            <?php echo Session::flash('msg_reg_sucesss') ?>
                        </div>
                    <?php endif ?>

                    <?php if (Session::data('wrong_info') != null) : ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="ri-checkbox-circle-fill" style="font-size: 24px; margin-right: 16px;"></i>
                            <?php echo Session::flash('wrong_info') ?>
                        </div>
                    <?php endif ?>
                    <!-- Alert -->

                    <!-- Card -->
                    <div class="card">
                        <div class="card-body p-8">
                            <!-- Form -->
                            <form class="mb-4" method="post">
                                <!-- Email -->
                                <div class="mb-4">
                                    <label for="email" class="mb-2">Email</label>
                                    <input type="email" id="email" name="email" class="form-control form-control-solid form-control-lg" placeholder="Tên đăng nhập">
                                    <?php if (Session::data('emailError') != null) : ?>
                                        <p class="form-text text-danger"> <?php echo Session::flash('emailError') ?></p>
                                    <?php endif ?>
                                </div>
                                <!-- Email -->

                                <!-- Password -->
                                <div class="mb-4">
                                    <label for="password" class="mb-2">Mật khẩu</label>
                                    <input type="password" id="password" name="password" class="form-control form-control-solid form-control-lg" placeholder="Mật khẩu">
                                </div>
                                <!-- Password -->

                                <!-- Remember -->
                                <div class="d-flex justify-content-between mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="" id="remember-check" checked>
                                        <label class="form-check-label" for="remember-check">
                                            Ghi nhớ đăng nhập
                                        </label>
                                    </div>
                                    <a href="reset.html" class="no-underline">Quên mật khẩu?</a>
                                </div>
                                <!-- Remember -->

                                <!-- Button -->
                                <div class="d-grid">
                                    <button class="btn btn-lg btn-primary" type="submit">Đăng nhập</button>
                                </div>
                                <!-- Button -->
                            </form>
                            <!-- Form -->

                            <!-- Text -->
                            <p class="text-center mb-0">
                                Bạn chưa có tài khoản?
                                <a href="/register">Đăng ký ngay!!!</a>
                            </p>
                            <!-- Text -->
                        </div>
                    </div>
                    <!-- Card -->
                </div>
            </div>
        </div>
    </div>
    <!-- Layout -->

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./js/app.min.js"></script>
    <!-- Scripts -->
</body>

</html>