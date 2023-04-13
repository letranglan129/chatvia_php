const pickerOptions = {
	locale: 'vi',
	onEmojiSelect: e => {
		messageInput.innerHTML += `<img class="emoji-icon" src='https://projectertest.000webhostapp.com/emoji/${e.unified}.png' onError="this.onError=null;console.clear();this.src='https://twemoji.maxcdn.com/2/72x72/${e.unified}.png'"> `;
	},
};
const picker = new EmojiMart.Picker(pickerOptions);

let activeChat;
let friendList = [];
let groupConversation = [];
let douConversation = [];
let conversations = [];
let limit = 2;
let offset = 20;

const chatContent = document.querySelector('.chat-content .container-fluid');
const chatWrap = document.querySelector('.main .chat');
const groupsTabList = document.querySelector('#groups-tab-list');
const allConversationTab = document.querySelector('#all-conversation-tab');
const messageForm = document.forms['message-form'];
const messageInput = document.querySelector('#msg-input');
const searchForm = document.forms['form-search'];
const createGroupForm = document.forms['createGroup'];
const updateUserInfoForm = document.forms['updateUserInfo'];
const searchConversationForm = document.forms['searchConversationForm'];
const changePasswordForm = document.forms['changePasswordForm'];
const forwardMemberForm = document.forms['forwardMemberForm'];
const updateAvatarForm = document.forms['updateAvatarForm'];
const conversationListEl = document.querySelector('#conversation-list');
const chatInfoWrap = document.querySelector('#chatInfoWrap');
const settingAvatarPreview = document.querySelector('#settingAvatarPreview');
const updateAvatarInput = document.querySelector('#updateAvatarInput');
const chatHeaderAvatarEl = document.querySelector('#chat-header-avatar');
const menuDropdownConversation = document.querySelector('#menu-dropdown-conversation');
const searchConversationResult = document.querySelector('#searchConversationResult');

const userId = Number(USER?.id);
const conn = new WebSocket(`ws://localhost:9001?id=${USER?.id}`);

const swiper = new Swiper('.swiper', {
	loop: true,

	// If we need pagination
	pagination: {
		el: '.swiper-pagination',
		clickable: true,
	},
});

tippy('#emoji-btn', {
	content: `<div id="emoji-warp"></div>`,
	allowHTML: true,
	hideOnClick: true,
	interactive: true,
	trigger: 'click',
	arrow: false,
	appendTo: document.body,
	flipOnUpdate: true,
	sticky: true,
	onMount: function () {
		document.querySelector('#emoji-warp').appendChild(picker);
	},
});

async function showAccountModal(id) {
	$.ajax({
		type: 'GET',
		url: `/user?id=${id}`,
		success: function (data) {
			let response = JSON.parse(data);

			document.querySelector('#modal-account .avatar').innerHTML = response.avatar
				? `<img src='${response.avatar}' alt='' >`
				: `<span class='avatar-label bg-soft-success text-success fs-3 '>${compactName(
						response.fullname,
				  )}</span>`;
			document.querySelector('#modal-account .name').innerHTML = response.fullname;
			document.querySelector('#modal-account .email .email-content').innerHTML = response.email;
			document.querySelector('#modal-account .phone .phone-content').innerHTML = response.phone;
			var myModal = new bootstrap.Modal(document.getElementById('modal-account'), {
				keyboard: false,
			});
			myModal.show();
		},
	});
}

function cancelRequestAddFriend(friendId) {
	conn.send(
		JSON.stringify({
			command: 'cancelRequestAddFriend',
			userId: USER?.id || -1,
			friendId,
		}),
	);

	document.querySelector('#search-friends-button').click();
}

function blockUser(friendId) {
	conn.send(
		JSON.stringify({
			command: 'blockUser',
			userId: USER?.id || -1,
			friendId,
		}),
	);
}

function unlockUser(friendId) {
	conn.send(
		JSON.stringify({
			command: 'unlockUser',
			userId: USER?.id || -1,
			friendId,
		}),
	);
}

function handleFileSelect(event, cb) {
	const files = event.target.files;
	const fileCount = files.length;
	const fileDataArray = [];

	for (let i = 0; i < fileCount; i++) {
		const file = files[i];
		const reader = new FileReader();

		reader.onload = function (event) {
			fileDataArray.push({
				name: file.name,
				type: file.type,
				size: file.size,
				data: event.target.result,
			});

			if (fileDataArray.length == fileCount && cb) {
				cb(fileDataArray);
			}
		};

		reader.readAsDataURL(file);
	}
}

function compactName(fullname) {
	let nameArray = fullname.split(' ');

	if (nameArray.length >= 2) {
		let lastName = nameArray[0];
		let firstName = nameArray[nameArray.length - 1];
		return lastName[0] + firstName[0];
	} else {
		return fullname[0];
	}
}

messageInput.addEventListener('keyup', function (e) {
	if (e.key == 'Enter') {
		this.innerHTML = '';
	}
});

messageInput.addEventListener('keydown', function (e) {
	if (e.key == 'Enter' && messageInput.innerHTML.trim() != '') {
		conn.send(
			JSON.stringify({
				senderId: USER?.id || -1,
				groupId: activeChat?.type == 'dou' ? activeChat?.groupId : activeChat?.groupInfo?.id,
				msg: this.innerHTML,
				command: 'sendMessage',
			}),
		);
		this.innerHTML = '';
		getConversation();
	}
});

function sendAddFriend(id) {
	conn.send(
		JSON.stringify({
			senderId: USER?.id || -1,
			receiverId: id,
			command: 'addFriend',
			senderName: USER?.fullname,
		}),
	);
	document.querySelector('#search-friends-button').click();
}

function accecptFriend(senderId, receiverId, notifyId) {
	conn.send(
		JSON.stringify({
			senderId,
			receiverId,
			notifyId,
			command: 'accecptFriend',
		}),
	);
}

function startChatPrivate(senderId, receiverId) {
	if (!senderId || !receiverId) return;

	document.querySelector('.main').classList.add('main-visible');
	document.querySelector('.user-chat-main-header').classList.remove(`user-card-${activeChat?.receiver?.id}`);
	document.querySelector('.user-chat-main-header').classList.remove(`online`);
	document.querySelector('.user-chat-main-header').classList.add(`user-card-${receiverId}`);

	conn.send(
		JSON.stringify({
			id: USER?.id || -1,
			command: 'getOnline',
		}),
	);
	conn.send(
		JSON.stringify({
			senderId,
			receiverId,
			command: 'startChatPrivate',
		}),
	);
}

function unfriend(userId, friendId) {
	conn.send(
		JSON.stringify({
			command: 'unfriend',
			userId,
			friendId,
		}),
	);
	document.querySelector('#search-friends-button').click();
}

function getConversation() {
	conn.send(
		JSON.stringify({
			command: 'getConversation',
			userId: Number(USER?.id || -1),
		}),
	);
	conn.send(
		JSON.stringify({
			id: USER?.id || -1,
			command: 'getOnline',
		}),
	);
}

function startChatMulti(groupId) {
	document.querySelector('.user-chat-main-header').classList.remove(`user-card-${activeChat?.receiver?.id}`);
	document.querySelector('.user-chat-main-header').classList.remove(`online`);
	document.querySelector('.main').classList.add('main-visible');
	conn.send(
		JSON.stringify({
			command: 'startChatMulti',
			userId: Number(USER?.id || -1),
			groupId,
		}),
	);
}

function deleteMessage(messageId, groupId, type = 'onlyMe') {
	conn.send(
		JSON.stringify({
			command: 'deleteMessage',
			type,
			userId: Number(USER?.id || -1),
			groupId,
			messageId,
		}),
	);
}

function deleteConversation(groupId, userId) {
	conn.send(
		JSON.stringify({
			command: 'deleteConversation',
			groupId,
			userId,
		}),
	);
}

function outGroup(groupId, userId) {
	conn.send(
		JSON.stringify({
			command: 'outGroup',
			groupId,
			userId,
		}),
	);
}

function setOwnerGroup(groupId, userId) {
	conn.send(
		JSON.stringify({
			command: 'setOwnerGroup',
			groupId,
			userId,
		}),
	);
}

function deleteGroup(groupId, ownerId) {
	conn.send(
		JSON.stringify({
			command: 'deleteGroup',
			groupId,
			ownerId,
		}),
	);
}

const groupMessagesByDayAndTime = messages => {
	const result = {};
	messages
		.sort((a, b) => Number(new Date(a.sent_at)) - Number(new Date(b.sent_at)))
		.forEach(message => {
			const date = message.sent_at.slice(0, 10);
			if (!result[date]) {
				result[date] = [];
			}

			const lastArr = result[date][result[date].length - 1];
			const lastMessage = lastArr ? lastArr[lastArr.length - 1] : null;

			if (
				lastMessage &&
				new Date(message.sent_at) - new Date(lastMessage.sent_at) <= 600000 &&
				message.sender_id == lastMessage.sender_id
			) {
				lastArr.push(message);
			} else {
				result[date].push([message]);
			}
		});
	return result;
};

function renderFriendList(el, list) {
	el.innerHTML = Object.keys(list)
		.map(
			key => `
            <div>
                <!-- Letter -->
                <h5 class="p-2 text-primary">
                    ${key}
                </h5>
                <!-- Letter -->

                <!--  Friends List -->
                <ul class="list-unstyled">
                    <!-- Chat Link -->
                    ${list[key]
						.map(
							friend => `
                        <li class="card contact-item mb-3 user-card user-card-${friend?.id}">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <!-- Avatar -->
                                <div class="avatar me-4" onclick="showAccountModal(${friend?.id})" >
                                    ${
										friend?.avatar
											? `<img src="${friend?.avatar}" alt="" id="settingAvatarPreview">`
											: `<span class="avatar-label bg-soft-primary text-primary">${compactName(
													friend.fullname,
											  )}</span>`
									}
                                </div>
                                <!-- Avatar -->

                                <!-- Content -->
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex align-items-center mb-1">
                                        <h5 class="text-truncate mb-0 me-auto">${friend.fullname}
                                        </h5>
                                    </div>
                                    <div class="text-online">
                                        <div class="text-truncate me-auto">Trực tuyến</div>
                                    </div>
                                </div>
                                <!-- Content -->

                                <!-- Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-icon btn-base btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-more-fill"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="startChatPrivate(${
												USER?.id || -1
											} ,${friend?.id})">Trò chuyện<i class="ri-message-2-line"></i></a>
                                        </li>
										<li>
                                            <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="unfriend(${
												USER?.id || -1
											} ,${friend?.id})">Hủy kết bạn<i class="ri-user-unfollow-line"></i></a>
                                        </li>
                                        <li>
                                            <div class="dropdown-divider"></div>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="blockUser(${
												friend?.id
											})">Chặn<i class="ri-forbid-line"></i></a>
                                        </li>
                                    </ul>
                                </div>
                                <!-- Dropdown -->
                            </div>
                        </div>
                    </li>
                        `,
						)
						.join('')}
                    <!-- Chat Link -->
                </ul>
                <!--  Friends List -->
            </div>
        `,
		)
		.join('');
}

function renderGroupListCheckbox(el, list, name = 'group-checkbox', groupName = 'groupMember', operator = '=') {
	console.log(list);
	switch (operator) {
		case '=':
			el.innerHTML = `<div><ul class="list-unstyled"><!-- Letter -->
                <h5 class="p-2 text-primary">
                    Nhóm
                </h5>
                    <!-- Chat Link -->
                    ${list
						.map(
							group => `
                        <li class="card contact-item mb-3 user-card group-card-${group.groupId}">
                        <div class="card-body">
                            <label class="d-flex align-items-center" for="${name}-${group.groupId}">
                                <!-- Avatar -->
                                <div class="avatar me-4">
								${
									group?.avatar
										? `<img src="${group?.avatar}" alt="" id="settingAvatarPreview">`
										: `<span class="avatar-label bg-soft-primary text-primary">${compactName(
												group.groupName,
										  )}</span>`
								}
                                </div>
                                <!-- Avatar -->

                                <!-- Content -->
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex align-items-center mb-1">
                                        <h5 class="text-truncate mb-0 me-auto">${group.groupName}
                                        </h5>
                                    </div>
                                    <div class="text-online">
                                        <div class="text-truncate me-auto">Trực tuyến</div>
                                    </div>
                                </div>
                                <!-- Content -->

                                <!-- Check -->
                                <div class="form-check">
                                    <input class="form-check-input" id="${name}-${
								group.groupId
							}" type="checkbox" name='${groupName}[]' value="${group.groupId}" >
                                </div>
                                <!-- Check -->
                            </label>
                        </div>
                    </li>
                        `,
						)
						.join('')}
                    <!-- Chat Link -->
                </ul></div>`;
			break;

		default:
			el.innerHTML += `
			<div>
			<!-- Letter -->
                <h5 class="p-2 text-primary">
                    Nhóm
                </h5>
			<ul class="list-unstyled">
                    <!-- Chat Link -->
                    ${list
						.map(
							group => `
                        <li class="card contact-item mb-3 user-card group-card-${group.groupId}">
                        <div class="card-body">
                            <label class="d-flex align-items-center" for="${name}-${group.groupId}">
                                <!-- Avatar -->
                                <div class="avatar me-4">
								${
									group?.avatar
										? `<img src="${group?.avatar}" alt="" id="settingAvatarPreview">`
										: `<span class="avatar-label bg-soft-primary text-primary">${compactName(
												group.groupName,
										  )}</span>`
								}
                                </div>
                                <!-- Avatar -->

                                <!-- Content -->
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex align-items-center mb-1">
                                        <h5 class="text-truncate mb-0 me-auto">${group.groupName}
                                        </h5>
                                    </div>
                                    <div class="text-online">
                                        <div class="text-truncate me-auto">Trực tuyến</div>
                                    </div>
                                </div>
                                <!-- Content -->

                                <!-- Check -->
                                <div class="form-check">
                                    <input class="form-check-input" id="${name}-${
								group.groupId
							}" type="checkbox" name='${groupName}[]' value="${group.groupId}" >
                                </div>
                                <!-- Check -->
                            </label>
                        </div>
                    </li>
                        `,
						)
						.join('')}
                    <!-- Chat Link -->
                </ul></div>`;
			break;
	}
}

function renderFriendListCheckbox(el, list, name = 'user-checkbox', groupName = 'groupMember') {
	el.innerHTML = Object.keys(list)
		.map(
			key => `
            <div>
                <!-- Letter -->
                <h5 class="p-2 text-primary">
                    ${key}
                </h5>
                <!-- Letter -->

                <!--  Friends List -->
                <ul class="list-unstyled">
                    <!-- Chat Link -->
                    ${list[key]
						.map(
							friend => `
                        <li class="card contact-item mb-3 user-card user-card-${
							(Number(USER?.id || -1) == Number(friend.friend_id)
								? friend?.user_id
								: friend?.friend_id) || friend?.id
						}">
                        <div class="card-body">
                            <label class="d-flex align-items-center" for="${name}-${
								(Number(USER?.id || -1) == Number(friend.friend_id)
									? friend?.user_id
									: friend?.friend_id) || friend?.id
							}">
                                <!-- Avatar -->
                                <div class="avatar me-4">
                                    ${
										friend?.avatar
											? `<img src="${friend?.avatar}" alt="" id="settingAvatarPreview">`
											: `<span class="avatar-label bg-soft-primary text-primary">${compactName(
													friend.fullname,
											  )}</span>`
									}
                                </div>
                                <!-- Avatar -->

                                <!-- Content -->
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex align-items-center mb-1">
                                        <h5 class="text-truncate mb-0 me-auto">${friend.fullname}
                                        </h5>
                                    </div>
                                    <div class="text-online">
                                        <div class="text-truncate me-auto">Trực tuyến</div>
                                    </div>
                                </div>
                                <!-- Content -->

                                <!-- Check -->
                                <div class="form-check">
                                    <input class="form-check-input" id="${name}-${
								(Number(USER?.id || -1) == Number(friend.friend_id)
									? friend?.user_id
									: friend?.friend_id) || friend?.id
							}" type="checkbox" name='${groupName}[]' value="${
								(Number(USER?.id || -1) == Number(friend.friend_id)
									? friend?.user_id
									: friend?.friend_id) || friend?.id
							}" >
                                </div>
                                <!-- Check -->
                            </label>
                        </div>
                    </li>
                        `,
						)
						.join('')}
                    <!-- Chat Link -->
                </ul>
                <!--  Friends List -->
            </div>
        `,
		)
		.join('');
}

function renderConversationList(el, list, unreadList) {
	el.innerHTML = list
		.map(item => {
			let unread = unreadList.filter(x => x.group_id == item.group_id);
			return `<li class="card contact-item mb-3 user-card ${
				item.group_id == activeChat?.groupId ? 'active' : ''
			} user-card-${item.receiverId}" onclick='startChatPrivate(${Number(USER?.id || -1)},${item.receiverId})'>
                            <a class="contact-link"></a>
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <!-- Avatar -->
                                    <div class="avatar avatar-online me-4">
                                        ${
											item?.avatar
												? `<img src="${item?.avatar}" alt="" id="settingAvatarPreview">`
												: `<span class="avatar-label bg-soft-primary text-primary">${compactName(
														item.receiverName,
												  )}</span>`
										}
                                    </div>
                                    <!-- Avatar -->

                                    <!-- Content -->
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex align-items-center mb-1">
                                            <h5 class="text-truncate mb-0 me-auto">${item.receiverName}</h5>
                                            <p class="small text-muted text-nowrap ms-4 mb-0">${new Date(
												item.sent_at,
											).getHours()}:${String(new Date(item.sent_at).getMinutes()).padStart(
				2,
				'0',
			)}</p>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="line-clamp me-auto">
                                                ${
													Number(USER?.id || -1) == Number(item.sender_id)
														? `Bạn: ${item.message}`
														: item.message || ''
												}
                                            </div>
                                            ${
												unread.length !== 0
													? `<span class="badge rounded-pill bg-primary ms-2">${unread.length}</span>`
													: ''
											}
                                           
                                        </div>
                                    </div>
                                    <!-- Content -->
                                </div>
                            </div>
                        </li>`;
		})
		.join('');
}

function renderGroupConversationList(el, list, unreadList) {
	el.innerHTML = list
		.map(item => {
			let unread = unreadList.filter(x => x.group_id == item.groupId);
			return `
                <li class="card contact-item mb-3 ${item.groupId == activeChat?.groupInfo?.id ? 'active' : ''}">
                    <a class="contact-link" onclick="startChatMulti(${item.groupId})"></a>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <!-- Avatar -->
                            <div class="avatar avatar-online me-4">
                                ${
									item?.groupAvatar
										? `<img src="${item?.groupAvatar}" alt="" id="settingAvatarPreview">`
										: `<span class="avatar-label bg-soft-primary text-primary">${compactName(
												item.groupName,
										  )}</span>`
								}
                            </div>
                            <!-- Avatar -->

                            <!-- Content -->
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex align-items-center mb-1">
                                    <h5 class="text-truncate mb-0 me-auto">${item?.groupName}</h5>
                                    <p class="small text-muted text-nowrap ms-4 mb-0">${new Date(
										item.sent_at,
									).getHours()}:${String(new Date(item.sent_at).getMinutes()).padStart(2, '0')}</p>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="line-clamp me-auto">${
										Number(USER?.id || -1) == Number(item.sender_id)
											? `Bạn: ${item.message}`
											: item.message || ''
									}</div>
                                    ${
										unread.length !== 0
											? `<span class="badge rounded-pill bg-primary ms-2">${unread.length}</span>`
											: ''
									}
                                </div>
                            </div>
                            <!-- Content -->
                        </div>
                    </div>
                </li>
        `;
		})
		.join('');
}

function renderMessage(format, list) {
	console.log(list);
	return `<div class="message ${Number(USER?.id || -1) == Number(list[0].sender_id) ? 'self' : ''}">
        
                    <div class="avatar avatar-sm ${
						list[0]?.sender_id == 0 ? 'd-none' : ''
					}"  onclick="showAccountModal(${list[0]?.sender_id})" >
					${
						list[0]?.avatar
							? `<img src="${list[0]?.avatar}" alt="" id="settingAvatarPreview">`
							: `<span class="avatar-label bg-soft-primary text-primary">${compactName(
									list[0].fullname,
							  )}</span>`
					}
                            </div>
                    <div class="message-wrap ${list[0]?.sender_id == 0 ? 'w-100' : ''}">
                        ${list
							.map(item => {
								switch (item.format) {
									case 'text':
										return `<div class="message-item ${
											item?.viewed_at && Number(item.sender_id) == userId ? `seen` : ''
										}" id="message-item-${item.id}">
                                                <div class="message-content ${
													list[0]?.sender_id == 0 ? 'm-auto message-system' : ''
												}">

                                                    <h6 class="mb-0 text-warning message-sender  ${
														list[0]?.sender_id == 0 ? 'd-none' : ''
													}">${item.fullname}</h6>
                                                    <span class="${list[0]?.sender_id == 0 ? 'text-sm' : ''}">${
											item.message
										}</span>
                                                    <div class="${list[0]?.sender_id == 0 ? 'd-none' : ''}">
                                                        <small class="text-muted w-100">${new Date(
															item.sent_at,
														).getHours()}:${String(
											new Date(item.sent_at).getMinutes(),
										).padStart(2, '0')}
                                                            <i class="ri-check-double-line align-bottom text-success fs-5 seen-icon" title="${new Date(
																item.viewed_at,
															).toLocaleString('vi')}"></i>
                                                        </small>
                                                    </div>
                                                </div>
                                                
                                                <div class="dropdown align-self-center ${
													list[0]?.sender_id == 0 ? 'd-none' : ''
												}">
                                                    <button class="btn btn-icon btn-base btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ri-more-2-fill"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item d-flex align-items-center justify-content-between" data-bs-toggle="modal" 
															data-id="${item.id}"
															data-bs-target="#forward-message-modal">Chuyển tiếp
                                                                <i class="ri-share-line"></i>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="deleteMessage(${
																item.id
															}, ${item.group_id})" href="#">Xóa phía tôi
                                                                <i class="ri-delete-bin-line"></i>
                                                            </a>
                                                        </li>

														${
															Number(USER?.id || -1) == Number(list[0].sender_id)
																? `<li>
                                                            <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="deleteMessage(${item.id}, ${item.group_id}, 'all')" href="#">Xóa với mọi người
                                                                <i class="ri-delete-bin-line"></i>
                                                            </a>
                                                        </li>`
																: ''
														}
                                                    </ul>
                                                </div>

                                            </div>`;

									case 'image':
										return `
                                            <div class="message-item ${
												item?.viewed_at && Number(item.sender_id) == userId ? `seen` : ''
											}" id="message-item-${item.id}">
                                                <div class="message-content">

                                                    <h6 class="mb-0 text-warning message-sender">${item.fullname}</h6>
                                                <div class='shared-image-list'>
                                                            <span>
                                                                ${item?.images
																	?.map(
																		image => `
                                                                    <a target="blank" class='shared-image d-inline-block mb-2 mx-1' href='${image.href}'>
                                                                        <img class='img-fluid rounded-2' src='${image.href}' alt='preview' data-action='zoom'>
                                                                    </a>
                                                                    `,
																	)
																	.join('')}
                                                                </span>
                                                        </div>
                                                    <div>
                                                        <small class="text-muted w-100">${new Date(
															item.sent_at,
														).getHours()}:${String(
											new Date(item.sent_at).getMinutes(),
										).padStart(2, '0')}
                                                            <i class="ri-check-double-line align-bottom text-success fs-5 seen-icon" title="${new Date(
																item.viewed_at,
															).toLocaleString('vi')}"></i>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="dropdown align-self-center">
                                                    <button class="btn btn-icon btn-base btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ri-more-2-fill"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <a class="dropdown-item d-flex align-items-center justify-content-between" data-bs-toggle="modal" 
															data-id="${item.id}"
															data-bs-target="#forward-message-modal">Chuyển tiếp
                                                                <i class="ri-share-line"></i>
                                                            </a>
                                                        <li>
                                                            <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="deleteMessage(${
																item.id
															}, ${item.group_id})"  href="#">Xóa phía tôi
                                                                <i class="ri-delete-bin-line"></i>
                                                            </a>
                                                        </li>
														${
															Number(USER?.id || -1) == Number(list[0].sender_id)
																? `<li>
                                                            <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="deleteMessage(${item.id}, ${item.group_id}, 'all')" href="#">Xóa với mọi người
                                                                <i class="ri-delete-bin-line"></i>
                                                            </a>
                                                        </li>`
																: ''
														}
                                                    </ul>
                                                </div>

                                            </div>
                                    `;

									case 'file': {
										return `<div class="message-item ${
											item?.viewed_at && Number(item.sender_id) == userId ? `seen` : ''
										}" id="message-item-${item.id}">
                                        <div class="message-content">
                                            <div class="d-flex align-items-center">
                                                <a target="blank" download="filename" href="${
													item.images[0].href
												}" class="btn btn-lg btn-icon btn-secondary rounded-circle me-3">
                                                    <i class="ri-download-line"></i>
                                                </a>
                                                <div>
                                                    <h5 class="mb-0">${item.images[0].name}</h5>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="dropdown align-self-center">
                                            <button class="btn btn-icon btn-base btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-more-2-fill"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item d-flex align-items-center justify-content-between" data-bs-toggle="modal" 
															data-id="${item.id}"
															data-bs-target="#forward-message-modal">Chuyển tiếp
                                                                <i class="ri-share-line"></i>
                                                            </a>
                                                        <li>
                                                            <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="deleteMessage(${
																item.id
															}, ${item.group_id})"  href="#">Xóa phía tôi
                                                                <i class="ri-delete-bin-line"></i>
                                                            </a>
                                                        </li>
														${
															Number(USER?.id || -1) == Number(list[0].sender_id)
																? `<li>
                                                            <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="deleteMessage(${item.id}, ${item.group_id}, 'all')" href="#">Xóa với mọi người
                                                                <i class="ri-delete-bin-line"></i>
                                                            </a>
                                                        </li>`
																: ''
														}
                                            </ul>
                                        </div>
                                    </div>`;
									}
								}
							})
							.join('')}
                                        </div>
                                    </div>`;
}

function renderMessageList(el, list) {
	el.innerHTML = Object.keys(list)
		.map(
			key => `
            <div class="separator">
                    <span class="separator-title fs-7 ls-1">${new Date(key).toLocaleDateString('vi')}</span>
                </div>
            ${list[key].map(item => renderMessage(item?.format, item)).join('')}
        `,
		)
		.join('');
}

function renderMenuDropdownConversation(el, type = 'dou', isOwner) {
	switch (type) {
		case 'dou':
			el.innerHTML = `
				<li>
					<a class="dropdown-item d-flex align-items-center justify-content-between" id="chatDetailDeleteBtn">Xóa tất cả tin nhắn
						<i class="ri-delete-bin-line"></i>
					</a>
				</li>
				<li>
					<div class="dropdown-divider"></div>
				</li>
				<li>
					<a class="dropdown-item d-flex align-items-center justify-content-between" id="chatDetailBlockBtn">Chặn
						<i class="ri-forbid-line"></i>
					</a>
				</li>`;
			break;
		case 'multi':
			el.innerHTML = `
				<li>
					<a class="dropdown-item d-flex align-items-center justify-content-between" id="chatDetailDeleteBtn">Xóa tất cả tin nhắn
						<i class="ri-delete-bin-line"></i>
					</a>
				</li>
				${
					isOwner
						? `<li>
					<a class="dropdown-item d-flex align-items-center justify-content-between" id="chatDetailDeleteGroupBtn">Xóa nhóm chat
						<i class="ri-delete-bin-line"></i>
					</a>
				</li>`
						: ''
				}
				<li>
					<div class="dropdown-divider"></div>
				</li>
				<li>
					<a class="dropdown-item d-flex align-items-center justify-content-between" id="chatDetailOutBtn">Rời khỏi nhóm
						<i class="ri-forbid-line"></i>
					</a>
				</li>`;
			break;
	}
}

function renderChatInfo(info, type, files) {
	switch (type) {
		case 'dou':
			chatInfoWrap.innerHTML = `
                <!-- User Info -->
                <div class="text-center p-4 pt-14">
                    <!-- Avatar -->
                    <div class="avatar avatar-xl mb-4">
                        ${
							info?.avatar
								? `<img src="${info?.avatar}" alt="" id="settingAvatarPreview">`
								: `<span class="avatar-label bg-soft-primary text-primary">${compactName(
										info.fullname,
								  )}</span>`
						}
                    </div>
                    <!-- Avatar -->

                    <!-- Text -->
                    <h5>${info.fullname}</h5>
                    <!-- Text -->

                    <!-- Text -->
                    <div class="text-center">
                        <span class="text-muted mb-0">${info?.describe}</span>
                    </div>
                    <!-- Text -->
                </div>
                <!-- User Info -->

                <!-- Segmented Control -->
                <div class="text-center mb-2">
                    <ul class="nav nav-pills nav-segmented" id="pills-tab-user-profile" role="tablist">
                        <!-- About -->
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-about-tab" data-bs-toggle="pill" data-bs-target="#pills-about" type="button" role="tab" aria-controls="pills-about" aria-selected="true">Thông tin</button>
                        </li>
                        <!-- About -->

                        <!-- Files -->
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-files-tab" data-bs-toggle="pill" data-bs-target="#pills-files" type="button" role="tab" aria-controls="pills-files" aria-selected="false">Tệp tin</button>
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
                            ${
								info?.fullname
									? `<li class="list-group-item py-4">
                                <h6 class="mb-1">Họ tên</h6>
                                <p class="text-truncate mb-0">${info.fullname}</p>
                            </li>`
									: ''
							}
                            ${
								info?.email
									? `<li class="list-group-item py-4">
                                <h6 class="mb-1">Email</h6>
                                <p class="text-truncate mb-0">${info.email}</p>
                            </li>`
									: ''
							}
                            ${
								info?.phone
									? `<li class="list-group-item py-4">
                                <h6 class="mb-1">Số điện thoại</h6>
                                <p class="text-truncate mb-0">${info.phone}</p>
                            </li>`
									: ''
							}
                            ${
								info?.address
									? `<li class="list-group-item py-4">
                                <h6 class="mb-1">Địa chỉ</h6>
                                <p class="text-truncate mb-0">${info.address}</p>
                            </li>`
									: ''
							}
                        </ul>
                    </div>
                    <!-- About -->

                    <!-- Files -->
                    <div class="tab-pane fade" id="pills-files" role="tabpanel" aria-labelledby="pills-files-tab">
                        <ul class="list-group list-group-flush">
                            <!-- File 1 -->
                            ${files
								.map(file => {
									return `<li class="list-group-item py-4">
                                <div class="row align-items-center gx-4">
                                    <!-- Icon -->
                                    <div class="col-auto">
                                        <div class="avatar avatar-sm">
                                            <span class="avatar-label">
                                                <i class="ri-file-line"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Icon -->

                                    <!-- Text -->
                                    <div class="col overflow-hidden">
                                        <h6 class="text-truncate mb-1">${file.name}</h6>
                                        <ul class="list-inline m-0">
                                            <li class="list-inline-item">
                                                <p class="text-uppercase text-muted mb-0 fs-6">${formatFileSize(
													file?.size || 0,
												)}</p>
                                            </li>

                                            <li class="list-inline-item">
                                                <p class="text-uppercase text-muted mb-0 fs-6">${file.name
													.split('.')
													.pop()}</p>
                                            </li>
                                        </ul>
                                    </div>
                                    <!-- Text -->

                                    <!-- Dropdown -->
                                    <div class="col-auto">
                                        <div class="dropdown">
                                            <button class="btn btn-icon btn-base btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-more-fill"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-right">
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="#">Download<i class="ri-download-line"></i></a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="#">Share<i class="ri-share-line"></i></a>
                                                </li>
                                                <li>
                                                    <div class="dropdown-divider"></div>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="#">Delete<i class="ri-delete-bin-line"></i></a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <!-- Dropdown -->
                                </div>
                            </li>`;
								})
								.join('')}
                            <!-- File 1 -->
                        </ul>
                    </div>
                    <!-- Files -->
                </div>
                <!-- Tab Content -->
                `;
			break;
		case 'multi': {
			chatInfoWrap.innerHTML = `
                <!-- User Info -->
                <div class="text-center p-4 pt-14">
                    <!-- Avatar -->
                    <div class="avatar avatar-xl mb-4">
                        ${
							info?.avatar
								? `<img src="${info?.avatar}" alt="" id="settingAvatarPreview">`
								: `<span class="avatar-label bg-soft-primary text-primary">${compactName(
										info.name,
								  )}</span>`
						}
                    </div>
                    <!-- Avatar -->

                    <!-- Text -->
                    <h5>${info.name}</h5>
                    <!-- Text -->

                    <!-- Text -->
                    <div class="text-center">
                        <span class="text-muted mb-0">${info?.desc}</span>
                    </div>
                    <!-- Text -->
                </div>
                <!-- User Info -->

                <!-- Segmented Control -->
                <div class="text-center mb-2">
                    <ul class="nav nav-pills nav-segmented" id="pills-tab-user-profile" role="tablist">
                        <!-- About -->
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-about-tab" data-bs-toggle="pill" data-bs-target="#pills-about" type="button" role="tab" aria-controls="pills-about" aria-selected="true">Thông tin</button>
                        </li>
                        <!-- About -->

                        <!-- Files -->
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-files-tab" data-bs-toggle="pill" data-bs-target="#pills-files" type="button" role="tab" aria-controls="pills-files" aria-selected="false">Tệp tin</button>
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
							${
								info?.members
									? `<li class="list-group-item py-4" id="memberOfGroup">
                               <div class="d-flex align-items-center justify-content-between">
                               	 <h6 class="mb-1">Thành viên (${info?.members?.length})</h6>
									${
										info?.owner == USER?.id
											? `<button data-bs-toggle="modal" 
															data-id="${info.id}"
															data-bs-target="#add-member-modal" class="btn btn-info btn-sm">Thêm</button>`
											: ''
									}
                               </div>
								<ul class="list-unstyled">
                    <!-- Chat Link -->
                    ${info?.members
						.map(
							friend => `
                        <li class="card contact-item mb-3 user-card user-card-${friend?.id}">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <!-- Avatar -->
                                <div class="avatar me-4" onclick="showAccountModal(${friend?.id})" >
                                    ${
										friend?.avatar
											? `<img src="${friend?.avatar}" alt="" id="settingAvatarPreview">`
											: `<span class="avatar-label bg-soft-primary text-primary">${compactName(
													friend.fullname,
											  )}</span>`
									}
									${
										info?.owner == friend?.id
											? `<span style="    position: absolute;
												color: var(--bs-yellow);
												background: rgba(0,0,0,0.3);
												width: 20px;
												height: 20px;
												font-size: 12px;
												display: flex;
												align-items: center;
												justify-content: center;
												border-radius: 50%;
												bottom: -3px;
												left: 0px;">
											<i class="ri-key-2-fill"></i>
										</span>`
											: ''
									}
                                </div>
                                <!-- Avatar -->

                                <!-- Content -->
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex align-items-center mb-1">
                                        <h5 class="text-truncate mb-0 me-auto">${friend.fullname} ${
								friend?.id == USER?.id ? `(tôi)` : ''
							}
                                        </h5>
                                    </div>
                                    <div class="text-online">
                                        <div class="text-truncate me-auto">Trực tuyến</div>
                                    </div>
                                </div>
                                <!-- Content -->

                                <!-- Dropdown -->
                                ${
									friend?.id != USER?.id
										? `<div class="dropdown">
                                    <button class="btn btn-icon btn-base btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ri-more-fill"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="startChatPrivate(${
												USER?.id || -1
											}, ${friend.id})">Trò chuyện<i class="ri-message-2-line"></i></a>
                                        </li>
										${(function () {
											switch (friend?.status) {
												case 'pending':
													return `<li>
                                                                    <a class="dropdown-item d-flex align-items-center justify-content-between" onClick="sendAddFriend(${friend.id})">Hủy yêu cầu kết bạn<i class="ri-user-unfollow-line"></i></a>
                                                                </li>`;
												case 'accepted':
													return `<li>
                                                                    <a class="dropdown-item d-flex align-items-center justify-content-between" onClick="sendAddFriend(${friend.id})">Xóa bạn bè<i class="ri-user-shared-line"></i></a>
                                                                </li>`;
												default:
													return `<li>
                                                                    <a class="dropdown-item d-flex align-items-center justify-content-between" onClick="sendAddFriend(${friend.id})">Thêm bạn bè<i class="ri-user-add-line"></i></a>
                                                                </li>`;
											}
										})()}
										${
											info?.owner == USER?.id
												? `
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="outGroup(${info?.id},${friend?.id})">Xóa khỏi nhóm<i class="ri-logout-box-r-line"></i></a>
                                        </li>`
												: ''
										}
										${
											info?.owner == USER?.id
												? `
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="setOwnerGroup(${info?.id},${friend?.id})">Đặt thành nhóm trưởng<i class="ri-logout-box-r-line"></i></a>
                                        </li>`
												: ''
										}
                                        <li>
                                            <div class="dropdown-divider"></div>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="blockUser(${
												friend?.id
											})">Chặn<i class="ri-forbid-line"></i></a>
                                        </li>
                                    </ul>
                                </div>`
										: ''
								}
                                <!-- Dropdown -->
                            </div>
                        </div>
                    </li>
                        `,
						)
						.join('')}
                    <!-- Chat Link -->
                </ul>
                            </li>`
									: ''
							}
                        </ul>
                    </div>
                    <!-- About -->

                    <!-- Files -->
                    <div class="tab-pane fade" id="pills-files" role="tabpanel" aria-labelledby="pills-files-tab">
                        <ul class="list-group list-group-flush">
                            <!-- File 1 -->
                            ${files
								.map(file => {
									return `<li class="list-group-item py-4">
                                <div class="row align-items-center gx-4">
                                    <!-- Icon -->
                                    <div class="col-auto">
                                        <div class="avatar avatar-sm">
                                            <span class="avatar-label">
                                                <i class="ri-file-line"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Icon -->

                                    <!-- Text -->
                                    <div class="col overflow-hidden">
                                        <h6 class="text-truncate mb-1">${file.name}</h6>
                                        <ul class="list-inline m-0">
                                            <li class="list-inline-item">
                                                <p class="text-uppercase text-muted mb-0 fs-6">${formatFileSize(
													file?.size || 0,
												)}</p>
                                            </li>

                                            <li class="list-inline-item">
                                                <p class="text-uppercase text-muted mb-0 fs-6">${file.name
													.split('.')
													.pop()}</p>
                                            </li>
                                        </ul>
                                    </div>
                                    <!-- Text -->

                                    <!-- Dropdown -->
                                    <div class="col-auto">
                                        <div class="dropdown">
                                            <button class="btn btn-icon btn-base btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-more-fill"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-right">
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="#">Download<i class="ri-download-line"></i></a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="#">Share<i class="ri-share-line"></i></a>
                                                </li>
                                                <li>
                                                    <div class="dropdown-divider"></div>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="#">Delete<i class="ri-delete-bin-line"></i></a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <!-- Dropdown -->
                                </div>
                            </li>`;
								})
								.join('')}
                            <!-- File 1 -->
                        </ul>
                    </div>
                    <!-- Files -->
                </div>
                <!-- Tab Content -->
                `;
			break;
		}
	}
}

function renderNotification(el, list) {
	el.innerHTML = list
		.map(notify => {
			let createdDate = new Date(Date.parse(notify.created_at));
			let formattedTime = createdDate.getHours() + ':' + createdDate.getMinutes().toString().padStart(2, '0');
			let msg = notify.payload;
			let btn = '';
			let img =
				notify['from_id'] != null
					? notify['avatar']
						? `<img src="${notify?.avatar}" alt="" id="settingAvatarPreview">`
						: `<span class='avatar-label bg-soft-success text-success'>${compactName(
								notify['fullname'],
						  )}</span>`
					: "<span class='avatar-label bg-soft-success text-success'><i class='ri-settings-3-line'></i></span>";
			let name = notify['from_id'] != null ? notify['fullname'] : 'Hệ thống';

			switch (notify['type']) {
				case 'friend_request': {
					msg = 'Gửi lời mời kết bạn.';
					btn = `<div class='card-footer'>
									<div class='row gx-4'>
										<div class='col'>
											<a href='#' class='btn btn-secondary btn-sm w-100'>Hủy</a>
										</div>
										<div class='col'>
											<a onClick='accecptFriend(${notify['from_id']}, ${notify['user_id']}, ${notify['id']})' class='btn btn-primary btn-sm w-100'>Chấp nhận</a>
										</div>
									</div>
								</div>`;
					break;
				}
				case 'friend_request_accepted': {
					msg = 'Đã chấn nhận lời mời kết bạn.';
					break;
				}
			}

			return `<div class='card mb-3 card-notify' data-id='${notify['id']}'  id='notify-${notify['id']}'>
                            <div class='card-body'>
                                <div class='d-flex align-items-center'>
                                    <!-- Avatar -->
                                    <div class='avatar me-4'>
                                        ${img}
                                    </div>

                                    <div class='flex-grow-1'>
                                        <div class='d-flex align-items-center overflow-hidden'>
                                            <h5 class='me-auto text-break mb-0'>${name}</h5>
                                            <span class='small text-muted text-nowrap ms-2'>${formattedTime}</span>
                                        </div>

                                        <div class='d-flex align-items-center'>
                                            <div class='line-clamp me-auto'>
                                            ${msg}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            ${btn}
                        </div>`;
		})
		.join('');
}

function renderAllConversation(el, list, unreadList) {
	el.innerHTML = list
		.map(item => {
			if (item.type == 'multi') {
				let unread = unreadList.filter(x => x.group_id == item.groupId);
				return `
                <li class="card contact-item mb-3 ${item.groupId == activeChat?.groupInfo?.id ? 'active' : ''}">
                    <a class="contact-link" onclick="startChatMulti(${item.groupId})"></a>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <!-- Avatar -->
                            <div class="avatar avatar-online me-4">
                                ${
									item?.groupAvatar
										? `<img src="${item?.groupAvatar}" alt="" id="settingAvatarPreview">`
										: `<span class="avatar-label bg-soft-primary text-primary">${compactName(
												item.groupName,
										  )}</span>`
								}
                            </div>
                            <!-- Avatar -->

                            <!-- Content -->
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex align-items-center mb-1">
                                    <h5 class="text-truncate mb-0 me-auto">${item?.groupName}</h5>
                                    <p class="small text-muted text-nowrap ms-4 mb-0">${new Date(
										item.sent_at,
									).getHours()}:${String(new Date(item.sent_at).getMinutes()).padStart(2, '0')}</p>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="line-clamp me-auto">${
										Number(USER?.id || -1) == Number(item.sender_id)
											? `Bạn: ${item.message}`
											: item.message || ''
									}</div>
                                    ${
										unread.length !== 0
											? `<span class="badge rounded-pill bg-primary ms-2">${unread.length}</span>`
											: ''
									}
                                </div>
                            </div>
                            <!-- Content -->
                        </div>
                    </div>
                </li>
        `;
			} else {
				let unread = unreadList.filter(x => x.group_id == item.group_id);
				return `<li class="card contact-item mb-3 user-card ${
					item.group_id == activeChat?.groupId ? 'active' : ''
				} user-card-${item.receiverId}" onclick='startChatPrivate(${Number(USER?.id || -1)},${
					item.receiverId
				})'>
								<a class="contact-link"></a>
								<div class="card-body">
									<div class="d-flex align-items-center">
										<!-- Avatar -->
										<div class="avatar avatar-online me-4">
											${
												item?.avatar
													? `<img src="${item?.avatar}" alt="" id="settingAvatarPreview">`
													: `<span class="avatar-label bg-soft-primary text-primary">${compactName(
															item.receiverName,
													  )}</span>`
											}
										</div>
										<!-- Avatar -->
	
										<!-- Content -->
										<div class="flex-grow-1 overflow-hidden">
											<div class="d-flex align-items-center mb-1">
												<h5 class="text-truncate mb-0 me-auto">${item.receiverName}</h5>
												<p class="small text-muted text-nowrap ms-4 mb-0">${new Date(item.sent_at).getHours()}:${String(
					new Date(item.sent_at).getMinutes(),
				).padStart(2, '0')}</p>
											</div>
											<div class="d-flex align-items-center">
												<div class="line-clamp me-auto">
													${Number(USER?.id || -1) == Number(item.sender_id) ? `Bạn: ${item.message}` : item.message || ''}
												</div>
												${unread.length !== 0 ? `<span class="badge rounded-pill bg-primary ms-2">${unread.length}</span>` : ''}
											   
											</div>
										</div>
										<!-- Content -->
									</div>
								</div>
							</li>`;
			}
		})
		.join('');
}

conn.onopen = function (e) {
	getConversation();

	conn.send(
		JSON.stringify({
			command: 'getNotifications',
			userId: USER?.id || -1,
		}),
	);

	conn.send(
		JSON.stringify({
			id: USER?.id || -1,
			command: 'getOnline',
		}),
	);
};

window.onunload = function () {
	conn.send(
		JSON.stringify({
			command: 'close',
		}),
	);
};

conn.onmessage = function (e) {
	const { event, ...data } = JSON.parse(e.data);
	console.log(event, data);
	switch (event) {
		case 'onError': {
			showToast({
				text: 'Đã xảy ra lỗi. Làm mới trang và thử lại',
				type: 'error',
			});
			break;
		}

		case 'cancelRequestAddFriend': {
			conn.send(
				JSON.stringify({
					command: 'getNotifications',
					userId: USER?.id || -1,
				}),
			);
			break;
		}

		case 'onBlockUser': {
			conn.send(
				JSON.stringify({
					command: 'getFriends',
				}),
			);
			break;
		}

		case 'onGetNotifications': {
			renderNotification(document.querySelector('.notification-list-yestoday .list-wrap'), data.notifyReaded);
			renderNotification(document.querySelector('.notification-list-today .list-wrap'), data.notifyUnread);
			let nDot = data.notifyUnread.length;
			if (nDot) {
				document.querySelector('.nav-notify .dot').innerHTML = nDot;
				document.querySelector('.nav-notify .dot').classList.remove('d-none');
			} else {
				document.querySelector('.nav-notify .dot').classList.add('d-none');
			}
			break;
		}

		case 'onUnblockUser':
		case 'onNewMessage': {
			if (activeChat?.type == 'dou' && activeChat?.groupId == data?.groupId) {
				startChatPrivate(activeChat?.sender?.id, activeChat?.receiver?.id);
			}

			if (activeChat?.type == 'multi' && activeChat?.groupInfo?.id == data?.groupId) {
				startChatMulti(activeChat?.groupInfo?.id);
			}

			if (activeChat?.groupId == data?.groupId) {
				chatContent.scrollIntoView({
					behavior: 'smooth',
					block: 'end',
					inline: 'nearest',
				});
			}

			getConversation();

			if (activeChat) {
				conn.send(
					JSON.stringify({
						groupId: activeChat?.type == 'dou' ? activeChat?.groupId : activeChat?.groupInfo?.id,
						messages:
							activeChat?.messages.filter(m => Number(USER?.id || -1) !== Number(m.sender_id)) || [],
						userId: Number(USER?.id || -1),
						command: 'sentMessage',
					}),
				);
			}
			break;
		}

		case 'onSendAddFriend': {
			showToast({
				text: data.msg,
				type: data.status,
			});
			break;
		}

		case 'onUnfriend': {
			conn.send(
				JSON.stringify({
					command: 'getFriends',
				}),
			);
			break;
		}

		case 'onAcceptFriend': {
			conn.send(
				JSON.stringify({
					command: 'getNotifications',
					userId: USER?.id || -1,
				}),
			);
			showToast({
				text: data.msg,
				type: 'success',
			});

			conn.send(
				JSON.stringify({
					command: 'getFriends',
				}),
			);
			break;
		}

		case 'onGetFriends': {
			friendList = data.friends.reduce((prev, cur) => {
				let firstLetter = cur['fullname'][0].toUpperCase();
				if (prev[firstLetter]) {
					prev[firstLetter].push(cur);
				} else {
					prev[firstLetter] = [cur];
				}
				return prev;
			}, {});
			renderFriendList(document.querySelector('.friend-list'), friendList);
			renderFriendListCheckbox(document.querySelector('#select-member-tab'), friendList);
			break;
		}

		case 'onStartChatPrivate': {
			const nameGroupEl = document.querySelector('#name-group');
			const chatFooter = document.querySelector('.chat-footer');
			chatWrap.classList.remove('d-none');
			nameGroupEl.innerHTML = data.receiver.fullname;

			const filterDateMessage = data.messages
				.sort((a, b) => Number(new Date(a.sent_at)) - Number(new Date(b.sent_at)))
				.reduce((prev, cur) => {
					let date = cur.sent_at.split(' ')[0];

					if (prev[date]) {
						prev[date].push(cur);
					} else {
						prev[date] = [cur];
					}

					return prev;
				}, {});

			if (activeChat?.groupId != data?.groupId) {
				offset = 10;
			}

			activeChat = data;

			renderMessageList(
				chatContent,
				groupMessagesByDayAndTime(
					data.messages
						.sort((a, b) => Number(new Date(b.sent_at)) - Number(new Date(a.sent_at)))
						.slice(0, offset),
				),
			);
			renderChatInfo(data.receiver, data.type, data.files);

			chatHeaderAvatarEl.innerHTML = data.receiver?.avatar
				? `<img src="${data.receiver?.avatar}" alt="" id="settingAvatarPreview">`
				: `<span class="avatar-label bg-soft-primary text-primary">${compactName(
						data.receiver.fullname,
				  )}</span>`;

			chatContent.scrollIntoView({
				block: 'end',
				inline: 'nearest',
			});

			if (activeChat) {
				conn.send(
					JSON.stringify({
						messages: 'all',
						userId: Number(USER?.id || -1),
						groupId: activeChat?.type == 'dou' ? activeChat?.groupId : activeChat?.groupInfo?.id,
						command: 'sentMessage',
					}),
				);
			}
			getConversation();

			renderMenuDropdownConversation(menuDropdownConversation, activeChat?.type, false);

			document.querySelector('#chatDetailDeleteBtn').onclick = function () {
				deleteConversation(data.groupId, USER?.id || '-1');
				getConversation();
			};

			document.querySelector('#chatDetailBlockBtn').onclick = function () {
				getConversation();
			};

			chatFooter.querySelector('.container-fluid').classList.remove('d-none');
			chatFooter.querySelector('.text-blocked')?.remove();

			if (data?.blocked?.userId == USER?.id) {
				chatFooter.querySelector('.container-fluid').classList.add('d-none');
				chatFooter.innerHTML += `<h6 class="w-100 text-center text-blocked">Bạn đã chặn người dùng này. <a onclick="unlockUser(${data?.receiver?.id})" class="text-primary" role="button"><u>Bỏ chặn???</u></a></h6> `;
			}

			if (data?.blocked?.blockedUserId == USER?.id) {
				chatFooter.querySelector('.container-fluid').classList.add('d-none');
				chatFooter.innerHTML += `<h6 class="w-100 text-center text-blocked">Cuộc trò chuyện không khả dụng</h6> `;
			}
			break;
		}

		case 'onGetConversation': {
			groupConversation = data.groupConversation;
			douConversation = data.conversation;

			let nDot = [...new Set(data.unreadMessages.map(x => x?.group_id))].length;
			if (nDot) {
				document.querySelector('.nav-chat .dot').innerHTML = nDot;
				document.querySelector('.nav-chat .dot').classList.remove('d-none');
			} else {
				document.querySelector('.nav-chat .dot').classList.add('d-none');
			}

			renderConversationList(
				conversationListEl,
				data.conversation
					.sort((a, b) => Number(new Date(b.sent_at)) - Number(new Date(a.sent_at)))
					.filter(x => Number(x.countMessage) > 0),
				data.unreadMessages,
			);

			renderGroupConversationList(
				groupsTabList,
				data.groupConversation
					.sort((a, b) => Number(new Date(b.sent_at)) - Number(new Date(a.sent_at)))
					.filter(x => Number(x.countMessage) > 0),
				data.unreadMessages,
			);

			renderAllConversation(
				allConversationTab,
				[...data.groupConversation, ...data.conversation]
					.sort((a, b) => Number(new Date(b.sent_at)) - Number(new Date(a.sent_at)))
					.filter(x => Number(x.countMessage) > 0),
				data.unreadMessages,
			);

			break;
		}

		case 'onGetOnline': {
			data.friends.forEach(friend => {
				document.querySelectorAll(`.user-card-${friend?.id}`).forEach(x => {
					if (friend.connectid !== '-1' && friend.connectid !== null) {
						x.classList.add('online');
					} else {
						x.classList.remove('online');
					}
				});
			});
			break;
		}

		case 'onResponseSent': {
			if (Array.isArray(data?.messages)) {
				data.messages.forEach(message => {
					const el = document.querySelector('#message-item-' + message.id);
					if (el) {
						let viewedAt = new Date(message.viewed_at);
						el.classList.add('seen');
						el.querySelector('.seen-icon')?.setAttribute('title', viewedAt.toLocaleString('vi'));
					}
				});
			}
			break;
		}

		case 'onStartChatMulti': {
			const chatFooter = document.querySelector('.chat-footer');
			const nameGroupEl = document.querySelector('#name-group');
			chatWrap.classList.remove('d-none');
			nameGroupEl.innerHTML = data?.groupInfo?.name || '';
			chatHeaderAvatarEl.innerHTML = data?.groupInfo?.avatar
				? `<img src="${data?.groupInfo?.avatar}" alt="" id="settingAvatarPreview">`
				: `<span class="avatar-label bg-soft-primary text-primary">${compactName(data?.groupInfo.name)}</span>`;
			activeChat = data;

			const filterDateMessage = data.messages
				.sort((a, b) => Number(new Date(a.sent_at)) - Number(new Date(b.sent_at)))
				.reduce((prev, cur) => {
					let date = cur.sent_at.split(' ')[0];

					if (prev[date]) {
						prev[date].push(cur);
					} else {
						prev[date] = [cur];
					}

					return prev;
				}, {});

			renderMessageList(
				chatContent,
				groupMessagesByDayAndTime(
					data.messages
						.sort((a, b) => Number(new Date(b.sent_at)) - Number(new Date(a.sent_at)))
						.slice(0, offset),
				),
			);
			renderChatInfo(data.groupInfo, data.type, data.files);
			chatContent.scrollIntoView({
				block: 'end',
				inline: 'nearest',
			});

			if (activeChat) {
				conn.send(
					JSON.stringify({
						messages: 'all',
						userId: Number(USER?.id || -1),
						groupId: activeChat?.type == 'dou' ? activeChat?.groupId : activeChat?.groupInfo?.id,
						command: 'sentMessage',
					}),
				);
			}
			getConversation();

			renderMenuDropdownConversation(
				menuDropdownConversation,
				activeChat?.type,
				data.groupInfo.owner == USER?.id,
			);

			document.querySelector('#chatDetailDeleteBtn').onclick = function () {
				deleteConversation(data?.groupInfo?.id || '0', USER?.id || '-1');
				getConversation();
			};

			document.querySelector('#chatDetailOutBtn').onclick = function () {
				outGroup(data?.groupInfo?.id || '0', USER?.id || '-1');
				getConversation();
			};

			if (document.querySelector('#chatDetailDeleteGroupBtn')) {
				document.querySelector('#chatDetailDeleteGroupBtn').onclick = function () {
					deleteGroup(data?.groupInfo?.id, USER?.id);
					getConversation();
				};
			}

			chatFooter.querySelector('.container-fluid').classList.remove('d-none');
			chatFooter.querySelector('.text-blocked')?.remove();
			break;
		}

		case 'onSendFile': {
			conn.send(
				JSON.stringify({
					senderId: USER?.id || -1,
					groupId: data?.groupId,
					msg: data?.imageHtml,
					command: 'sendMessage',
				}),
			);
			getConversation();
			break;
		}

		case 'onCreateChatGroup': {
			if (data?.userCreate == USER?.id) {
				showToast({
					text: 'Tạo nhóm thành công!!!',
					type: 'success',
				});
			} else {
				showToast({
					text: `Bạn vừa được ${data?.userCreateName} thêm vào nhóm ${data?.groupName}!!!`,
					type: 'success',
				});
			}

			getConversation();
			break;
		}

		case 'onForwardMessage': {
			if (data?.result == true) {
				showToast({
					text: 'Chuyển tiếp tin nhắn thành công!!!',
					type: 'success',
				});
			} else {
				showToast({
					text: `Chuyển tiếp tin nhắn thất bại`,
					type: 'error',
				});
			}
			break;
		}

		case 'onDeleteMessage': {
			if (data.type == 'all' || (data.type == 'onlyMe' && data.userId == USER?.id)) {
				getConversation();
				const messageEl = document.querySelector(`#message-item-${data.messageId}`);
				chatWrap.classList.remove('d-none');
				const wrapMessageEl = messageEl.parentElement;

				if (wrapMessageEl.children.length == 1) {
					wrapMessageEl.parentElement.remove();
				} else {
					messageEl.remove();
				}
			}

			break;
		}

		case 'onDeleteConversation': {
			if (data.result > 0) {
				$('.main').removeClass('main-visible');
			}
			break;
		}

		case 'onOutGroup': {
			if (data.result) {
				// showToast({
				// 	text: `Đã rởi khỏi nhóm`,
				// 	type: 'success',
				// });
			} else {
				showToast({
					text: `Đã xảy ra lỗi`,
					type: 'error',
				});
			}
			break;
		}

		case 'onSomeoneExitGroup': {
			getConversation();
			if (activeChat?.type == 'multi' && activeChat?.groupInfo?.id == data?.groupId) {
				startChatMulti(activeChat?.groupInfo?.id);
				getConversation();
			}

			if (data.userId == USER?.id && activeChat?.groupInfo?.id == data?.groupId) {
				document.querySelector('.main.main-visible').classList.remove('main-visible');
			}
			break;
		}

		case 'onAddMemberToGroup': {
			getConversation();

			if (activeChat?.type == 'multi' && activeChat?.groupInfo?.id == data?.groupId) {
				startChatMulti(activeChat?.groupInfo?.id);
			}

			break;
		}

		case 'onDeleteGroup': {
			getConversation();
			if (activeChat?.groupInfo?.id == data?.groupId) {
				document.querySelector('.main.main-visible').classList.remove('main-visible');
			}
			break;
		}
	}
};

messageForm.onsubmit = function (e) {
	e.preventDefault();
	if (messageInput.innerHTML.trim() != '') {
		conn.send(
			JSON.stringify({
				senderId: USER?.id || -1,
				groupId: activeChat?.type == 'dou' ? activeChat?.groupId : activeChat?.groupInfo?.id,
				msg: messageInput.innerHTML,
				command: 'sendMessage',
			}),
		);
	}

	getConversation();
	messageInput.innerHTML = '';
};

updateUserInfoForm.onsubmit = function (e) {
	e.preventDefault();
	const data = new FormData(updateUserInfoForm);
	$.ajax({
		type: 'POST',
		data: Object.fromEntries(data),
		url: `/user/update`,
		success: function (data) {
			let response = JSON.parse(data);
			if (response && response?.result == true) {
				showToast({
					text: 'Cập nhật thông tin thành công!!! Làm mới trang để hiển thị thay đổi',
					type: 'success',
				});
			}
		},
	});
};

changePasswordForm.onsubmit = function (e) {
	e.preventDefault();
	const data = new FormData(changePasswordForm);
	for (let [key, value] of data.entries()) {
		if (value.length < 6) {
			showToast({
				text: 'Mật khẩu tối thiểu 6 kí tự',
				type: 'error',
			});
			return;
		}
	}

	data.append('email', USER?.email || '');

	$.ajax({
		type: 'POST',
		data: Object.fromEntries(data),
		url: `/user/changePassword`,
		success: function (data) {
			let response = JSON.parse(data);
			if (response) {
				showToast({
					text: response?.message || '',
					type: response?.result == true ? 'success' : 'error',
				});

				document.querySelector('input[name="oldPassword"]').value = '';
				document.querySelector('input[name="newPassword"]').value = '';
				document.querySelector('input[name="reNewPassword"]').value = '';
			}
		},
	});
};

document.querySelector('#avatar-group-input').onchange = function (e) {
	document.querySelector('label[for="avatar-group-input"]').innerHTML = `<div class="avatar avatar-lg">
                                            <img src="${URL.createObjectURL(e.target.files[0])}">
                                        </div>`;
};

createGroupForm.onsubmit = function (e) {
	e.preventDefault();

	let data = {};
	Array.from(e.target.elements).forEach(el => {
		if ((el.type == 'text' || el.type == 'textarea') && el.name) {
			data = {
				...data,
				[el.name]: el.value,
			};
		}

		if (el.type == 'checkbox' && el.checked) {
			data = {
				...data,
				'groupMember[]': Array.isArray(data[el.name])
					? [...data[el.name], el.value].filter(i => i !== '' && i !== 0)
					: [el.value],
			};
		}
	});

	if (data['groupMember[]']?.length < 2) {
		return;
	}
	
	if (e.target.elements['avatar-group-input'].files.length > 0) {
		handleFileSelect({ target: e.target.elements['avatar-group-input'] }, file => {
			console.log(file);
			conn.send(
				JSON.stringify({
					...data,
					avatar: file[0],
					command: 'createGroup',
					senderId: USER?.id || -1,
					fullname: USER?.fullname || '',
				}),
			);
		});

		createGroupForm.reset();
	} else {
		conn.send(
			JSON.stringify({
				...data,
				command: 'createGroup',
				senderId: USER?.id || -1,
				fullname: USER?.fullname || '',
			}),
		);

		createGroupForm.reset();
	}
};

// ====================
searchForm.onsubmit = function (e) {
	e.preventDefault();
	const data = new FormData(searchForm);
	if (!data.get('q').trim()) {
		return;
	}
	$.ajax({
		type: 'GET',
		data: Object.fromEntries(data),
		url: `/user`,
		success: function (res) {
			const friendListEl = document.querySelector('#friends-tab .friend-list');
			friendListEl.classList.add('d-none');

			let result = JSON.parse(res);
			Array.from(friendListEl.parentElement.children).forEach((child, index) => {
				if (friendListEl.parentElement.children.length > 1 && index > 0) {
					child.remove();
				}
			});
			if (Array.isArray(result)) {
				friendListEl.parentElement.innerHTML += `
                        <ul class="list-unstyled mx-4" id="search-result">

						<li class=" m-4">Kết quả tìm kiếm với: ${data.get('q')}</li>

                        ${result
							.map(friend => {
								return `<li class="card contact-item mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <!-- Avatar -->
                                    <div class="avatar me-4" onclick="showAccountModal(${friend?.id})" >
                                         ${
												friend?.avatar
													? `<img src="${friend?.avatar}" alt="" id="settingAvatarPreview">`
													: `<span class="avatar-label bg-soft-primary text-primary">${compactName(
															friend.fullname,
													  )}</span>`
											}
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex align-items-center mb-1">
                                            <h5 class="text-truncate mb-0 me-auto">${friend.fullname}
                                            </h5>
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-icon btn-base btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-fill"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-right">
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="startChatPrivate(${
													USER?.id
												}, ${
									friend.id
								})" href="#">Trò chuyện<i class="ri-message-2-line"></i></a>
                                            </li>
                                            ${(function () {
												if (friend.blocked_user_id == USER?.id || friend.blockBy == USER?.id)
													return '';

												switch (friend.status) {
													case 'pending':
														return `<li>
																<a class="dropdown-item d-flex align-items-center justify-content-between" onClick="cancelRequestAddFriend(${friend.id})">Hủy yêu cầu kết bạn<i class="ri-edit-line"></i></a>
															</li>`;
													case 'accepted':
														return `<li>
																<a class="dropdown-item d-flex align-items-center justify-content-between" onClick="unfriend(${USER?.id || -1} ,${
															friend.id
														})">Hủy kết bạn<i class="ri-edit-line"></i></a>
															</li>`;

													default:
														return `<li>
																<a class="dropdown-item d-flex align-items-center justify-content-between" onClick="sendAddFriend(${friend.id})">Thêm bạn bè<i class="ri-edit-line"></i></a>
															</li>`;
												}
											})()}
                                            <li>
                                                <div class="dropdown-divider"></div>
                                            </li>
											
                                            ${
												friend.blockBy == USER?.id
													? `<li>
                                                <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="unlockUser(${friend?.id})">Bỏ chặn<i class="ri-forbid-line"></i></a>
                                            </li>`
													: `<li>
                                                <a class="dropdown-item d-flex align-items-center justify-content-between" onclick="blockUser(${friend?.id})">Chặn<i class="ri-forbid-line"></i></a>
                                            </li>`
											}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </li>`;
							})
							.join('')}

                        ${result.length == 0 ? '<li class="text-center">Không có kết quả trùng khớp</li>' : ''}

                    </ul>
                        `;
			}
		},
	});
};

searchForm.querySelector('input').oninput = function (e) {
	if (this.value.trim() == '') {
		const friendListEl = document.querySelector('#friends-tab .friend-list');
		friendListEl.classList.remove('d-none');
		document.querySelector('#search-result')?.remove();
	}
};

const fileInput = document.querySelector('#images-upload');

fileInput.addEventListener(
	'change',
	event => {
		handleFileSelect(event, data => {
			if (activeChat) {
				conn.send(
					JSON.stringify({
						groupId: activeChat?.type == 'dou' ? activeChat?.groupId : activeChat?.groupInfo?.id,
						senderId: USER?.id || -1,
						command: 'sendFile',
						files: data,
					}),
				);
			}
		});

		showToast({
			text: 'Đang tải tệp tin, vui lòng chờ...',
			type: 'warning',
		});
	},
	false,
);

document.querySelector('#forward-message-modal').addEventListener('show.bs.modal', function (e) {
	conn.send(
		JSON.stringify({
			id: USER?.id || -1,
			command: 'getOnline',
		}),
	);

	renderFriendListCheckbox(
		this.querySelector('form[name="forwardMemberForm"]'),
		friendList,
		'forward-user-checkbox',
		'member',
	);

	renderGroupListCheckbox(
		this.querySelector('form[name="forwardMemberForm"]'),
		groupConversation,
		'forward-group-checkbox',
		'groupMember',
		'+=',
	);

	document.querySelector('#submitForwardFormBtn').addEventListener(
		'click',
		function () {
			const data = new FormData(forwardMemberForm);
			const button = e.relatedTarget;
			const id = button.dataset.id;
			conn.send(
				JSON.stringify({
					command: 'forwardMessage',
					receiversPrivate: data.getAll('member[]'),
					receiversGroup: data.getAll('groupMember[]'),
					senderId: USER?.id || -1,
					messageId: id,
				}),
			);
			forwardMemberForm.reset();
		},
		{
			once: true,
		},
	);
});

document.querySelector('#add-member-modal').addEventListener('show.bs.modal', function (e) {
	conn.send(
		JSON.stringify({
			id: USER?.id || -1,
			command: 'getOnline',
		}),
	);
	let members = activeChat?.groupInfo?.members || [];

	let memberIds = [];
	let friends = [];
	members.forEach(m => {
		memberIds.push(m.id);
	});

	Object.keys(friendList).forEach(key => {
		friends.push(...friendList[key]);
	});

	renderFriendListCheckbox(
		this.querySelector('form[name="addMemberForm"]'),
		friends
			.filter(friend => !memberIds.includes(friend.id))
			.reduce((prev, cur) => {
				let firstLetter = cur['fullname'][0].toUpperCase();
				if (prev[firstLetter]) {
					prev[firstLetter].push(cur);
				} else {
					prev[firstLetter] = [cur];
				}
				return prev;
			}, {}),
		'add-member-user-checkbox',
		'add-member',
	);

	document.querySelector('#submitAddMemberFormBtn').addEventListener('click', function () {
		const data = new FormData(addMemberForm);
		conn.send(
			JSON.stringify({
				command: 'addMemberToGroup',
				members: data.getAll('add-member[]'),
				groupId: activeChat?.groupInfo?.id || 0,
			}),
		);
	});
});

updateAvatarInput.addEventListener('change', function (event) {
	const data = new FormData(updateAvatarForm);
	data.append('id', USER?.id || '');
	$.ajax({
		type: 'POST',
		data: data,
		contentType: false,
		processData: false,
		url: `/user/updateAvatar`,
		beforeSend: function () {
			showToast({
				text: 'Đang tải lên ảnh!',
				type: 'warning',
			});
		},
		success: function (data) {
			console.log(JSON.parse(data));
			showToast({
				text: 'Cập nhật ảnh thành công!!! \nLàm mới trang để hiển thị thay đổi',
				type: 'success',
			});
		},
	});
});

setInterval(() => {
	conn.send(
		JSON.stringify({
			id: USER?.id || -1,
			command: 'getOnline',
		}),
	);
}, 5000);

$('#nav-list a').on('click', function (e) {
	e.preventDefault();
	if (e.delegateTarget.hash == '#notifications-tab') {
		const cardNotifyEls = document.querySelectorAll('.notification-list-today .list-wrap .card-notify');
		const notifyIds = Array.from(cardNotifyEls).map(el => el.dataset.id);
		conn.send(
			JSON.stringify({
				command: 'readNotify',
				notifyIds,
			}),
		);
	} else {
		conn.send(
			JSON.stringify({
				command: 'getNotifications',
				userId: USER?.id || -1,
			}),
		);
	}
	createGroupForm.reset();
	searchConversationForm.reset();
	searchForm.reset();
	$(this).tab('show');
});

var myDropdown = document.getElementById('dropdown-type-conversation');
myDropdown.addEventListener('show.bs.dropdown', function (e) {
	Array.from(e.target.nextElementSibling.querySelectorAll('.dropdown-item')).forEach(el => {
		el.onclick = function () {
			let active = e.target.nextElementSibling.querySelector('.dropdown-item.active');

			active.classList.remove('active');
			e.target.innerHTML = this.innerHTML;
			this.classList.add('active');

			document.querySelector(`#showConversation ${active.dataset.target}`).classList.remove('show', 'active');
			document.querySelector(`#showConversation ${this.dataset.target}`).classList.add('show', 'active');
			document
				.querySelector(`#searchConversationResult ${active.dataset.target}`)
				.classList.remove('show', 'active');
			document.querySelector(`#searchConversationResult ${this.dataset.target}`).classList.add('show', 'active');
		};
	});
});

chatContent.parentElement.addEventListener('scroll', e => {
	if (e.target.scrollTop < 100) {
		let oldHeight = e.target.scrollHeight;

		if (
			activeChat.messages
				.sort((a, b) => Number(new Date(b.sent_at)) - Number(new Date(a.sent_at)))
				.slice(0, offset).length == activeChat.messages.length
		)
			return;

		offset += 20;
		renderMessageList(
			chatContent,
			groupMessagesByDayAndTime(
				activeChat.messages
					.sort((a, b) => Number(new Date(b.sent_at)) - Number(new Date(a.sent_at)))
					.slice(0, offset),
			),
		);

		let newHeight = e.target.scrollHeight;
		chatContent.parentElement.scrollTo(0, -(oldHeight - newHeight));
	}
});

searchConversationForm.onsubmit = function (e) {
	e.preventDefault();
	let mergeGroup = [...groupConversation, ...douConversation];

	const data = new FormData(searchConversationForm);
	let query = data.get('query-conversation');

	console.log(mergeGroup);
	let result = mergeGroup.filter(
		group =>
			group?.groupName?.toUpperCase()?.includes(query?.toUpperCase()) ||
			group?.receiverName?.toUpperCase()?.includes(query?.toUpperCase()),
	);
	searchConversationResult.querySelector('.search-title').innerHTML = `Kết quả tìm kiểm với: ${query}`;
	searchConversationResult.classList.remove('d-none');
	showConversation.classList.add('d-none');

	renderConversationList(
		searchConversationResult.querySelector('#conversation-list'),
		result
			.sort((a, b) => Number(new Date(b.sent_at)) - Number(new Date(a.sent_at)))
			.filter(x => Number(x.countMessage) > 0 && x?.type !== 'multi'),
		[],
	);

	renderGroupConversationList(
		searchConversationResult.querySelector('#groups-tab-list'),
		result
			.sort((a, b) => Number(new Date(b.sent_at)) - Number(new Date(a.sent_at)))
			.filter(x => Number(x.countMessage) > 0 && x?.type == 'multi'),
		[],
	);

	renderAllConversation(
		searchConversationResult.querySelector('#all-conversation-tab-list'),
		result
			.sort((a, b) => Number(new Date(b.sent_at)) - Number(new Date(a.sent_at)))
			.filter(x => Number(x.countMessage) > 0),
		[],
	);
};

searchConversationForm.querySelector('input').oninput = function (e) {
	if (e.target.value == '') {
		searchConversationResult.classList.add('d-none');
		showConversation.classList.remove('d-none');
	}
};