<form class="form" action="static_qrcode.php?type=skype" method="post" id="static_form" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
<!-- Input forms -->
    <div class="col-sm-4">
        <div class="form-group">
            <label>Skype username *</label>
            <input type="text" name="skype_username" value="" placeholder="" class="form-control">
        </div>
    </div>
    
    <?php include BASE_PATH.'/forms/qrcode_options.php'; ?>
<div class="col-sm-12 mb-2">
    <div class="row">
        <div class="col-6 col-md-3">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>    
    </div>
</div>
                
</form>