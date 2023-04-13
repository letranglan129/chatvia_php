<div class="tab-pane fade h-100" id="create-chat-tab" role="tabpanel">
    <div class="d-flex flex-column h-100">

        <!-- Tab Header -->
        <div class="tab-header d-flex align-items-center border-bottom">
            <ul class="d-flex justify-content-between align-items-center list-unstyled w-100 mx-4 mb-0">
                <!-- Title -->
                <li>
                    <h3 class="mb-0">Tạo cuộc trò chuyện</h3>
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

        <form name="createGroup"    method="POST" style="display: contents;">
            <!-- Segmented Control -->
            <div class="text-center mb-4 mt-4">
                <ul class="nav nav-pills nav-segmented" id="group-pills" role="tablist">
                    <!-- Direct Nav Item -->
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="pills-create-group-tab" data-bs-toggle="pill" href="#create-group-tab" role="tab" aria-controls="pills-create-group-tab" aria-selected="true">Nhóm</a>
                    </li>
                    <!-- Direct Nav Item -->

                    <!-- Group Nav Item -->
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="pills-select-member-tab" data-bs-toggle="pill" href="#select-member-tab" role="tab" aria-controls="pills-select-member-tab" aria-selected="false">Bạn bè</a>
                    </li>
                    <!-- Group Nav Item -->
                </ul>
            </div>
            <!-- Segmented Control -->

            <!-- Tab Content -->
            <div class="hide-scrollbar h-100">
                <div class="tab-content m-4 mt-0">
                    <div class="tab-pane fade show active" id="create-group-tab" role="tabpanel">

                        <!-- Group Card -->
                        <div class="card mb-3">
                            <div class="card-body pt-0">
                                <!-- Profile -->
                                <div class="profile text-center">
                                    <div class="profile-img text-primary">
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
                                    <label for="avatar-group-input" class="profile-content">
                                        <div class="avatar avatar-lg">
                                            <span class="avatar-label bg-success text-white fs-3">
                                                <i class="ri-image-line"></i>
                                            </span>
                                        </div>
                                    </label>

                                    <input type="file" accept=".jpg, .jpeg, .png" hidden name="avatar-group-input" id="avatar-group-input">
                                </div>
                                <!-- Profile -->

                                <!-- Group Name Input -->
                                <div class="my-4">
                                    <div class="input-group">
                                        <input type="text" name="groupName" required class="form-control form-control-lg form-control-solid" placeholder="Tên nhóm" aria-label="Group name">
                                    </div>
                                </div>
                                <!-- Group Name Input -->

                                <!-- About Input -->
                                <div class="mb-4">
                                    <div class="input-group">
                                        <textarea class="form-control form-control-lg form-control-solid" name="groupDesc" placeholder="Mô tả" rows="3"></textarea>
                                    </div>
                                </div>
                                <!-- About Input -->
                            </div>
                        </div>
                        <!-- Group Card -->

                    </div>
                    <div class="tab-pane fade" id="select-member-tab" role="tabpanel">
                        
                    </div>
                </div>
            </div>
            <!-- Tab Content -->

            <!-- Create Chat Footer -->
            <div class="create-chat-footer border-top p-4">
                <button class="btn btn-primary w-100">Tạo</button>
            </div>
            <!-- Create Chat Footer -->
        </form>
    </div>
</div>