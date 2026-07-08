<form class="form" action="static_qrcode.php?type=applink" method="post" id="static_form" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
    <?php include BASE_PATH.'/forms/qrcode_options.php'; ?>
<!-- Input forms -->
<div class="col-sm-12 mb-2">
    <div class="row">

    <div class="col-6 col-md-3">
        <div class="form-group">
            <label>Platform *</label>
            <select name="platform" id="applink-platform" class="form-control">
                <option value="android" selected>Android (intent link)</option>
                <option value="generic">Generic (custom scheme)</option>
            </select>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="form-group">
            <label>Scheme *</label>
            <input type="text" name="scheme" value="" placeholder="myapp" class="form-control">
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="form-group">
            <label>Path *</label>
            <input type="text" name="path" value="" placeholder="open?ref=123" class="form-control">
        </div>
    </div>

    <div class="col-6 col-md-3" id="applink-package-group">
        <div class="form-group">
            <label>Android package *</label>
            <input type="text" name="package" value="" placeholder="com.example.app" class="form-control">
        </div>
    </div>

    <div class="col-6 col-md-3" id="applink-fallback-group">
        <div class="form-group">
            <label>Fallback URL</label>
            <input type="text" name="fallback_url" value="" placeholder="https://play.google.com/store/apps/details?id=..." class="form-control">
        </div>
    </div>
    </div>
</div>

<script>
    (function () {
        var platformSelect = document.getElementById('applink-platform');
        var packageGroup = document.getElementById('applink-package-group');
        var fallbackGroup = document.getElementById('applink-fallback-group');

        function updateVisibility() {
            var isAndroid = platformSelect.value === 'android';
            packageGroup.style.display = isAndroid ? '' : 'none';
            fallbackGroup.style.display = isAndroid ? '' : 'none';
        }

        platformSelect.addEventListener('change', updateVisibility);
        updateVisibility();
    })();
</script>

<div class="col-sm-12 mb-2">
    <div class="row">
        <div class="col-6 col-md-3">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</div>

</form>
