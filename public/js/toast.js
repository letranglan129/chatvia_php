function showToast(option) {
	Toastify({
		text: option?.text,
		className: option?.type || 'success',
		gravity: 'bottom',
		position: 'left',
		duration: 4000,
		close: true,
		style: {
			background: '#121212',
			color: '#fff',
		},
		avatar: `/img/toast_${option?.type || 'success'}.png`,
	}).showToast();
}
