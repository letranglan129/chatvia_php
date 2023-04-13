 <?php

    use App\Libraries\Session;
    use App\Libraries\Ultis;

    ?>

 <div class="tab-pane fade h-100" id="settings-tab" role="tabpanel">
     <div class="d-flex flex-column h-100">
         <!-- Tab Header -->
         <div class="tab-header d-flex align-items-center border-bottom">
             <ul class="d-flex justify-content-between align-items-center list-unstyled w-100 mx-4 mb-0">
                 <!-- Title -->
                 <li>
                     <h3 class="mb-0">Cài đặt</h3>
                 </li>
                 <!-- Title -->

                 <!-- Buttons -->
                 <li>
                     <ul class="list-inline">
                         <!-- Menu Button -->
                         <li class="list-inline-item">
                             <button type="button" class="navigation-toggle btn btn-secondary btn-icon d-xl-none">
                                 <i class="ri-menu-line"></i>
                             </button>
                         </li>
                         <!-- Menu Button -->
                     </ul>
                 </li>
                 <!-- Buttons -->
             </ul>
         </div>
         <!-- Tab Header -->

         <!-- Search Input -->
         <div class="m-2">
         </div>
         <!-- Search Input -->

         <!-- Tab Content -->
         <div class="hide-scrollbar h-100">
             <div class="m-4 mt-0">
                 <!-- Profile -->
                 <div class="card mb-3">
                     <div class="card-body">
                         <div class="row align-items-center gx-4">
                             <!-- Avatar -->
                             <form class="col-auto" name="updateAvatarForm">
                                 <label for="updateAvatarInput" class="avatar avatar-online">
                                     <?php
                                        if (isset(Session::data('user')['avatar'])) {
                                            echo ("<img src='" . Session::data('user')['avatar'] . "' alt='' >");
                                        } else {
                                            echo ("<span class='avatar-label bg-soft-success text-success fs-3 '>" . Ultis::compactName(Session::data('user')['fullname']) . "</span>");
                                        }
                                        ?>

                                     <span class="position-absolute" style="    bottom: -3px;
                                        right: 0;
                                        background: #058fff;
                                        width: 24px;
                                        height: 24px;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        border-radius: 50%;">
                                         <i class="ri-camera-fill"></i>
                                     </span>
                                 </label>

                                 <input type="file" name="update-avatar" id="updateAvatarInput" hidden accept=".jpg, .jpeg, .png">
                             </form>
                             <!-- Avatar -->

                             <!-- Content -->
                             <div class="col">
                                 <h5 class="mb-1"><?php echo (Session::data('user') != null ? Session::data('user')['fullname'] : '') ?></h5>
                                 <p class="text-muted mb-0"><?php echo (Session::data('user') != null ? Session::data('user')['email'] : '') ?></p>
                             </div>
                             <!-- Content -->

                             <!-- Button -->
                             <a href="/logout" class="btn btn-sm btn-icon btn-base">
                                 <i class="ri-logout-box-line"></i>
                             </a>
                             <!-- Button -->
                         </div>
                     </div>
                 </div>
                 <!-- Profile -->

                 <!-- Account -->
                 <div class="mb-3">
                     <!-- Title -->
                     <div class="d-flex align-items-center mx-4 mb-3">
                         <small class="text-muted me-auto">Tài khoản</small>
                     </div>
                     <!-- Title -->

                     <!-- Accordion -->
                     <div class="card">
                         <div class="card-body">
                             <div class="accordion accordion-flush" id="accordion-account-settings">
                                 <div class="accordion-item">
                                     <div class="accordion-header" id="account-heading-1">
                                         <div class="accordion-button collapsed p-0 pb-4" role="button" data-bs-toggle="collapse" data-bs-target="#account-collapse-1" aria-expanded="false" aria-controls="account-collapse-1">
                                             <div>
                                                 <h5 class="mb-1">Cài đặt tài khoản</h5>
                                                 <p class="text-muted mb-0">Cập nhật thông tin tài khoản</p>
                                             </div>
                                         </div>
                                     </div>
                                     <div id="account-collapse-1" class="accordion-collapse collapse" aria-labelledby="account-heading-1">
                                         <form method="post" name="updateUserInfo" class="accordion-body p-0 pb-4">
                                             <!-- Name Input -->
                                             <div class="mb-4">
                                                 <div class="input-group">
                                                     <input type="text" name="fullname" class="form-control form-control-lg form-control-solid" placeholder="Họ tên" aria-label="Họ tên" value="<?php echo (isset(Session::data('user')['fullname']) ? Session::data('user')['fullname'] : "") ?>">
                                                 </div>
                                             </div>
                                             <!-- Name Input -->

                                             <!-- Email Input -->
                                             <div class="mb-4">
                                                 <div class="input-group">
                                                     <input type="text" class="form-control form-control-lg form-control-solid" style="opacity: 0.5;" placeholder="Email" name="email" aria-label="Email" readonly value="<?php echo (isset(Session::data('user')['email']) ? Session::data('user')['email'] : "") ?>">
                                                 </div>
                                             </div>
                                             <!-- Email Input -->

                                             <!-- Phone Input -->
                                             <div class="mb-4">
                                                 <div class="input-group">
                                                     <input type="text" class="form-control form-control-lg form-control-solid" placeholder="Số điện thoại" name="phone" aria-label="Số điện thoại" value="<?php echo (isset(Session::data('user')['phone']) ? Session::data('user')['phone'] : "") ?>">
                                                 </div>
                                             </div>
                                             <!-- Phone Input -->

                                             <!-- About Input -->
                                             <div class="mb-4">
                                                 <div class="input-group">
                                                     <textarea class="form-control form-control-lg form-control-solid" placeholder="Mô tả" name="describe" rows="3"><?php echo (isset(Session::data('user')['describe']) ? Session::data('user')['describe'] : "") ?></textarea>
                                                 </div>
                                             </div>
                                             <!-- About Input -->

                                             <!-- Button -->
                                             <button type="submit" class="btn btn-lg btn-primary w-100">Lưu</button>
                                             <!-- Button -->
                                         </form>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                     <!-- Accordion -->
                 </div>
                 <!-- Account -->

                 <!-- Security -->
                 <div class="mb-3">
                     <!-- Title -->
                     <div class="d-flex align-items-center mx-4 mb-3">
                         <small class="text-muted me-auto">Bảo mật</small>
                     </div>
                     <!-- Title -->

                     <!-- Accordion -->
                     <div class="card">
                         <div class="card-body">
                             <div class="accordion accordion-flush" id="accordion-security-settings">
                                 <div class="accordion-item">
                                     <div class="accordion-header" id="security-heading-1">
                                         <div class="accordion-button collapsed p-0 pb-4" role="button" data-bs-toggle="collapse" data-bs-target="#security-collapse-1" aria-expanded="false" aria-controls="security-collapse-1">
                                             <div>
                                                 <h5 class="mb-1">Mật khẩu</h5>
                                                 <p class="text-muted mb-0">Quản lý mật khẩu của bạn</p>
                                             </div>
                                         </div>
                                     </div>
                                     <div id="security-collapse-1" class="accordion-collapse collapse" aria-labelledby="security-heading-1">
                                         <form name="changePasswordForm" class="accordion-body p-0 pb-4">
                                             <!-- Current password -->
                                             <div class="mb-4">
                                                 <div class="input-group">
                                                     <input type="password" name="oldPassword" class="form-control form-control-lg form-control-solid" placeholder="Mật khâu hiện tại" aria-label="Current password">
                                                 </div>
                                             </div>
                                             <!-- Current password -->

                                             <!-- New password -->
                                             <div class="mb-4">
                                                 <div class="input-group">
                                                     <input type="password" name="newPassword" class="form-control form-control-lg form-control-solid" placeholder="Mật khẩu mới" aria-label="New password">
                                                 </div>
                                             </div>
                                             <!-- New password -->

                                             <!-- Confirm password -->
                                             <div class="mb-4">
                                                 <div class="input-group">
                                                     <input type="password" name="reNewPassword" class="form-control form-control-lg form-control-solid" placeholder="Xác nhận mật khẩu" aria-label="Confirm password">
                                                 </div>
                                             </div>
                                             <!-- Confirm password -->

                                             <!-- Button -->
                                             <button type="submit" class="btn btn-lg btn-primary w-100">Lưu</button>
                                             <!-- Button -->
                                         </form>
                                     </div>
                                 </div>
                                 
                             </div>
                         </div>
                     </div>
                     <!-- Accordion -->
                 </div>
                 <!-- Security -->

             </div>
         </div>
         <!-- Tab Content -->
     </div>
 </div>