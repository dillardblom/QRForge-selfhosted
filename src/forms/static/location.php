<form class="form" action="static_qrcode.php?type=location" method="post" id="static_form" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
<!-- Input forms -->
    <div class="col-sm-8">
        <div class="form-group" style="position: relative;">
            <label for="location_search">Search address</label>
            <div class="input-group">
                <input type="text" id="location_search" class="form-control" placeholder="Search for an address or place...">
                <div class="input-group-append">
                    <button type="button" id="location_search_btn" class="btn btn-outline-secondary"><i class="fa fa-search"></i></button>
                </div>
            </div>
            <div id="location_search_results" class="list-group" style="position:absolute;z-index:1000;width:100%;"></div>
            <small class="form-text text-muted">Looks up coordinates via OpenStreetMap Nominatim (your search leaves this server and goes to nominatim.openstreetmap.org). Or enter coordinates directly below.</small>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="form-group">
            <label for="latitude">Latitude *</label>
            <input type="text" name="latitude" id="latitude" value="" placeholder="40.7127753" class="form-control">
        </div>
    </div>

    <div class="col-sm-4">
        <div class="form-group">
            <label for="longitude">Longitude *</label>
            <input type="text" name="longitude" id="longitude" value="" placeholder="-74.0059728" class="form-control">
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

<script src="dist/js/location-search.js"></script>
</form>