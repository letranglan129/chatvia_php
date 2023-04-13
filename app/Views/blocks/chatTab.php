<div class="tab-pane fade show active h-100" id="chats-tab" role="tabpanel">
    <div class="d-flex flex-column h-100">
        <!-- Tab Header -->
        <div class="tab-header d-flex align-items-center border-bottom">
            <ul class="d-flex justify-content-between align-items-center list-unstyled w-100 mx-4 mb-0">
                <!-- Title -->
                <li>
                    <h3 class="mb-0">Trò chuyện</h3>
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
        <div class="m-4">
            <form class="input-group" id="searchConversationForm">
                <input type="search" class="form-control form-control-lg form-control-solid" placeholder="Tìm kiếm cuộc trò chuyện" aria-label="Search user" name="query-conversation" aria-describedby="search-user-button">
                <button class="btn btn-secondary btn-lg" type="submit" id="search-user-button"><i class="ri-search-line"></i></button>
            </form>
        </div>
        <!-- Search Input -->

        <!-- Segmented Control -->
        <div class="dropdown m-1  ms-4 d-flex align-items-center justify-content-between">
            <span>Hiển thị với</span>
            <button class="btn btn-secondary dropdown-toggle btn-sm" type="button" id="dropdown-type-conversation" data-bs-toggle="dropdown" aria-expanded="false">
                Tất cả
            </button>
            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="dropdown-type-conversation">
                <li><a class="dropdown-item" data-target="#direct-tab">Bạn bè</a></li>
                <li><a class="dropdown-item" data-target="#groups-tab">Nhóm</a></li>
                <li><a class="dropdown-item active" data-target="#all-conversation-tab">Tất cả</a></li>
            </ul>
        </div>

        <!-- Segmented Control -->

        <!-- Tab Content -->
        <div class="hide-scrollbar h-100" id="showConversation">
            <div class="tab-content m-4 mt-1">
                <!-- Direct Chats Tab -->
                <div class="tab-pane fade" id="direct-tab" role="tabpanel">
                    <ul class="list-unstyled js-contact-list mb-0" id='conversation-list'>

                    </ul>
                </div>
                <!-- Direct Chats Tab -->

                <!-- Group Chats Tab -->
                <div class="tab-pane fade" id="groups-tab" role="tabpanel">
                    <ul class="list-unstyled js-contact-list mb-0" id="groups-tab-list">

                    </ul>
                </div>
                <div class="tab-pane fade show active" id="all-conversation-tab" role="tabpanel">
                    <ul class="list-unstyled js-contact-list mb-0" id="all-conversation-tab-list">

                    </ul>
                </div>
            </div>
        </div>
        <!-- Tab Content -->

        <div class="hide-scrollbar h-100 d-none" id="searchConversationResult">
            <p class="mx-4 search-title"></p>
            <div class="tab-content m-4 mt-1">
                <!-- Direct Chats Tab -->
                <div class="tab-pane fade" id="direct-tab" role="tabpanel">
                    <ul class="list-unstyled js-contact-list mb-0" id='conversation-list'>

                    </ul>
                </div>
                <!-- Direct Chats Tab -->

                <!-- Group Chats Tab -->
                <div class="tab-pane fade" id="groups-tab" role="tabpanel">
                    <ul class="list-unstyled js-contact-list mb-0" id="groups-tab-list">

                    </ul>
                </div>
                <div class="tab-pane fade show active" id="all-conversation-tab" role="tabpanel">
                    <ul class="list-unstyled js-contact-list mb-0" id="all-conversation-tab-list">

                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>