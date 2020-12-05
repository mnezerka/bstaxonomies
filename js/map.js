
var el = document.getElementById('bstaxonomies-map');

if (el) {
    // home - map center
    var center = [49.2202194, 16.5558572]

    // instancel of leaflet map
    var bstmap = L.map('bstaxonomies-map', { fullscreenControl: true }).setView(center, 12);

    // attributes to be added to map as static text
    var osmAttr = '&copy; <a href="http://openstreetmap.org" target="_blank">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/" target="_blank">CC-BY-SA</a>';
    var mapyCzAttr = '&copy; <a href="https://www.seznam.cz/" target="_blank">Seznam.cz, a.s</a>, ' + osmAttr;

    // legend control + div for rendering content
    var legendDiv = L.DomUtil.create('div', 'info legend');
    var legend = L.control({position: 'bottomleft'});
    legend.onAdd = function (map) {
        return legendDiv;
    };
    legend.addTo(bstmap);

    // tile layer - mapy.cz tourist map
    L.tileLayer('https://mapserver.mapy.cz/turist-m/{z}-{x}-{y}', {
        attribution: mapyCzAttr,
        minZoom: 2,
        maxZoom: 20,
        maxNativeZoom: 18,
        id: 'mapycz',
        tileSize: 256
    }).addTo(bstmap);

    // add markers
    let markers = [];
    for (let i = 0; i < params.tags.length; i++) {
        let tagData = params.tags[i];
        let marker = L.marker(tagData.loc);
        marker.bindPopup('<div class="bstmap-popup-content">' + tagData.link + '</div>');
        markers.push(marker);
    }

    let markersGroup = L.featureGroup(markers);
    markersGroup.addTo(bstmap);

    bstmap.fitBounds(markersGroup.getBounds());
}
