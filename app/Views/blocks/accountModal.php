 <?php

    use App\Libraries\Session;
    use App\Libraries\Ultis;
    ?>

 <div class="modal fade" id="modal-account" tabindex="-1" aria-labelledby="modal-account" aria-hidden="true">
     <div class="modal-dialog modal-dialog-centered">
         <div class="modal-content">
             <div class="profile text-center">
                 <div class="profile-img text-primary px-5">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 100" fill="currentColor">
                         <defs>
                             <style>
                                 .st1 {
                                     fill: #fff;
                                     opacity: 0.1;
                                 }
                             </style>
                         </defs>
                         <path d="M300,0v80c0,11-9,20-20,20H20C9,100,0,91,0,80V0H300z" />
                         <path class="st1" d="M50,71c-16,0-29,13-29,29h10c0-10.5,8.5-19,19-19s19,8.5,19,19h10C79,84,66,71,50,71z" />
                         <path class="st1" d="M31.6,0H21.3C21.8,1.6,22,3.3,22,5c0,10.5-8.5,19-19,19c-1,0-2-0.1-3-0.2v10.1C1,34,2,34,3,34c16,0,29-13,29-29
                                                                                                C32,3.3,31.8,1.6,31.6,0z" />
                         <path class="st1" d="M238.5,58C217.3,58,200,75.3,200,96.5c0,1.2,0,2.3,0.2,3.5h10.1c-0.1-1.2-0.2-2.3-0.2-3.5
                                                                                                c0-15.7,12.8-28.5,28.5-28.5S267,80.8,267,96.5c0,1.2-0.1,2.3-0.2,3.5h10.1c0.1-1.2,0.2-2.3,0.2-3.5C277,75.3,259.7,58,238.5,58z" />
                         <path class="st1" d="M299,22c-11,0-20-9-20-20c0-0.7,0-1.3,0.1-2h-10C269,0.7,269,1.3,269,2c0,16.5,13.5,30,30,30c0.3,0,0.7,0,1,0
                                                                                                V22C299.7,22,299.3,22,299,22z" />
                     </svg>
                 </div>
                 <div class="profile-content">
                     <!-- Avatar -->
                     <div class="avatar avatar-lg">
                             <?php
                                    if(isset(Session::data('user')['avatar'])) {
                                        echo ("<img src='" . Session::data('user')['avatar'] . "' alt='' >");
                                        
                                    } else {
                                        echo ("<span class='avatar-label bg-soft-success text-success fs-3 '>".Ultis::compactName(Session::data('user')['fullname'])."</span>");
                                    }
                                ?>
                     </div>
                     <!-- Avatar -->

                     <!-- Name -->
                     <h5 class="m-1 name"><?php echo (Session::data('user') != null ? Session::data('user')['fullname'] : '') ?></h5>
                     <!-- Name -->
                 </div>
             </div>

             <div class="modal-body p-0">
                 <ul class="list-group list-group-flush">
                     <!-- Email -->
                     <li class="list-group-item p-4 email">
                         <div class="row align-items-center">
                             <div class="col">
                                 <h5 class="mb-1">Email</h5>
                                 <p class="text-muted mb-0 email-content"><?php echo (Session::data('user') != null ? Session::data('user')['email'] : '') ?></p>
                             </div>
                             <div class="col-auto">
                                 <button type="button" class="btn btn-icon btn-light rounded-circle">
                                     <i class="ri-mail-line"></i>
                                 </button>
                             </div>
                         </div>
                     </li>
                     <!-- Email -->

                     <!-- Phone -->
                     <li class="list-group-item p-4 phone">
                         <div class="row align-items-center">
                             <div class="col">
                                 <h5 class="mb-1">Số điện thoại</h5>
                                 <p class="text-muted mb-0 phone-content"><?php echo (Session::data('user') != null ? Session::data('user')['phone'] : '') ?></p>
                             </div>
                             <div class="col-auto">
                                 <button type="button" class="btn btn-icon btn-light rounded-circle">
                                     <i class="ri-phone-line"></i>
                                 </button>
                             </div>
                         </div>
                     </li>
                     <!-- Phone -->

                 </ul>
             </div>

             <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                 <a href="/logout" class="btn btn-primary">Đăng xuất</a>
             </div>
         </div>
     </div>
 </div>