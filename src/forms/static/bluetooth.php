<form class="form" action="static_qrcode.php?type=bluetooth" method="post" id="static_form" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
    <?php include BASE_PATH.'/forms/qrcode_options.php'; ?>
<!-- Input forms -->
<div class="col-sm-12 mb-2">
    <small class="form-text text-muted mb-2">
        There is no OS-native "scan to pair" standard for Bluetooth like there is for Wifi, so this
        just encodes the device name and address for reference - whoever scans it still pairs
        manually via their Bluetooth settings.
    </small>
    <div class="row">

    <div class="col-6 col-md-3">
        <div class="form-group">
            <label>Device name *</label>
            <input type="text" name="device_name" value="" placeholder="" class="form-control">
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="form-group">
            <label>MAC address *</label>
            <input type="text" name="mac_address" value="" placeholder="AA:BB:CC:DD:EE:FF" class="form-control">
        </div>
    </div>
    </div>
</div>

<div class="col-sm-12 mb-2">
    <div class="row">
        <div class="col-6 col-md-3">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</div>

</form>
