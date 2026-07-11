(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('location_search');
        var searchBtn = document.getElementById('location_search_btn');
        var resultsBox = document.getElementById('location_search_results');
        var latInput = document.getElementById('latitude');
        var lngInput = document.getElementById('longitude');

        if (!searchInput || !searchBtn || !resultsBox || !latInput || !lngInput) {
            return;
        }

        function clearResults() {
            resultsBox.innerHTML = '';
        }

        function doSearch() {
            var query = searchInput.value.trim();
            if (!query) {
                return;
            }

            clearResults();
            resultsBox.innerHTML = '<div class="list-group-item">Searching...</div>';

            fetch('https://nominatim.openstreetmap.org/search?format=json&limit=5&q=' + encodeURIComponent(query))
                .then(function (response) { return response.json(); })
                .then(function (results) {
                    clearResults();

                    if (!results.length) {
                        resultsBox.innerHTML = '<div class="list-group-item">No results found.</div>';
                        return;
                    }

                    results.forEach(function (place) {
                        var item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = place.display_name;
                        item.addEventListener('click', function () {
                            latInput.value = place.lat;
                            lngInput.value = place.lon;
                            searchInput.value = place.display_name;
                            clearResults();
                        });
                        resultsBox.appendChild(item);
                    });
                })
                .catch(function () {
                    resultsBox.innerHTML = '<div class="list-group-item text-danger">Search failed (network error).</div>';
                });
        }

        searchBtn.addEventListener('click', doSearch);
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                doSearch();
            }
        });

        document.addEventListener('click', function (e) {
            if (e.target !== searchInput && !resultsBox.contains(e.target)) {
                clearResults();
            }
        });
    });
})();
