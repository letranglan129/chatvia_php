<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<form id="upload-form">
    <input type="file" name="img_avatar" id="img_avatar">
    <input type="submit" />
</form>

<script>
    $(document).ready(function() {
        $('#upload-form').submit(function(e) {
            e.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                type: 'POST',
                url: '<?php echo base_url('/upload'); ?>',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(data) {
                    console.log('Success');
                },
                error: function(data) {
                    console.log(data);
                }
            });
        });
    });
</script>