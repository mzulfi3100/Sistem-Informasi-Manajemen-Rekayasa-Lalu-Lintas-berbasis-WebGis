<?php $title="Peta Apill"?>
@extends('admin/template')
@section('content')
    <div id="map" style="height:650px; width: 1050px;"></div>
@stop
@section('script_peta')
    <script type="text/javascript">
        var satellite = L.tileLayer('http://{s}.google.com/vt?lyrs=s&x={x}&y={y}&z={z}',{
            maxZoom: 20,
            subdomains:['mt0','mt1','mt2','mt3']
        });

        var street = L.tileLayer('http://{s}.google.com/vt?lyrs=m&x={x}&y={y}&z={z}',{
            maxZoom: 20,
            subdomains:['mt0','mt1','mt2','mt3']
        });

        var hybrid = L.tileLayer('http://{s}.google.com/vt?lyrs=s,h&x={x}&y={y}&z={z}',{
            maxZoom: 20,
            subdomains:['mt0','mt1','mt2','mt3']
        });

        var map = L.map('map', {
            layers: [street, hybrid, satellite],
            center: [-5.420000, 105.292969],
            zoom: 12.4
        });

        var baseTree = [
            {
                label: 'Street',
                layer: street,
            },
            {
                label: 'Hybrid',
                layer: hybrid,
            },
            {
                label: 'Satellite',
                layer: satellite,
            },
        ];

        var kabupatenJson;

        $.ajax({
            url: "/kabupaten.geojson",
            async: false,
            dataType: 'json',
            success: function(data){
                kabupatenJson = data
            }
        });

        var kabupatenStyle = {
            "color": '#000000',
            "weight": 2,
            "opacity": 1,
            "fillOpacity": 0 ,
        };

        var atcs = L.icon({
            iconUrl: '/ATCS.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        var apill = L.icon({
            iconUrl: '/apill.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41] 
        });

        L.geoJSON(kabupatenJson, {
            style: kabupatenStyle
        }).addTo(map);

        var overlaysTree = 
            {
                label: 'Layers',
                selectAllCheckbox: 'Un/select all',
                children: [
                    {label: '<div id="onlysel">-Show only selected-</div>'},
                    {
                        label: "Apill",
                        selectAllCheckbox: true,
                        children: [
                            @foreach($apills as $apill)
                                {
                                    label: '<?= $apill->namaSimpang ?>',
                                    layer: L.geoJSON(<?= $apill->geoJsonApill ?>, {
                                        onEachFeature: function(feature, layer){
                                            layer.bindTooltip('<?= $apill->namaSimpang ?>');
                                            if('<?= $apill->terkoneksiATCS ?>' == 'Sudah'){
                                                layer.setIcon(atcs);
                                            }else{
                                                layer.setIcon(apill);
                                            }
                                        }
                                    }).addTo(map),
                                    name:   '<div style="max-height: 200px; overflow-y: auto"' +
                                                '<div class="card">' +
                                                    '<div class="card-header">' +
                                                        '<h3 class="card-title" style="text-align: center" >' + '<?= $apill->namaSimpang ?>' +'</h3>' +
                                                    '</div>' +
                                                    '<div class="card-body">' +
                                                        '<table class="table">' +
                                                            '<tbody>' +
                                                                '<tr>' +
                                                                    '<td>Terkoneksi ATCS</td>' +
                                                                    '<td>: ' + '<?= $apill->terkoneksiATCS ?>' + '</td>' +
                                                                '</tr>' +
                                                            '</tbody>' +
                                                        '</table>' +
                                                    '</div>' +
                                                '</div>' +
                                            '</div>' 
                                },
                            @endforeach
                        ]
                    },
                    {
                        label: 'Kecamatan',
                        selectAllCheckbox: true,
                        children: [
                            @foreach($kecamatans as $kec)
                                {
                                    label: '<?= $kec->namaKecamatan ?>', layer: L.geoJSON(<?= $kec->geoJsonKecamatan ?>, {
                                        style: {
                                            "color": "<?= $kec->warnaKecamatan ?>",
                                            "weight": 0,
                                            "opacity": 1,
                                            "fillOpacity": 0.5 ,
                                        },
                                    }),
                                    name: '<?= $kec->namaKecamatan ?>',
                                },
                            @endforeach
                        ]
                    },
                ]
            };

        var lay = L.control.layers.tree(baseTree, overlaysTree, {
                    namedToggle: true,
                    selectorBack: false,
                    closedSymbol: '&#8862; &#x1f5c0;',
                    openedSymbol: '&#8863; &#x1f5c1;',
                    collapseAll: 'Collapse all',
                    collapsed: true,
                    position: 'topleft'
                });

        var makePopups = function(node) {
            if (node.layer) {
                node.layer.bindPopup(node.name);
            }
            if (node.children) {
                node.children.forEach(function(element) { makePopups(element); });
            }
        };
        makePopups(overlaysTree);
        
        map.addControl(L.control.search({position: 'topleft'}));

        L.control.Legend({
            position: "bottomleft",
            collapsed: true,
            symbolWidth: 15,
            opacity: 1,
            column: 2,
            legends: [{
                label: "Terkoneksi ATCS",
                type: "image",
                url: "/ATCS.png"
            },  {
                label: "Tidak Terkoneksi ATCS",
                type: "image",
                url: "/apill.png"
            }]
        }).addTo(map);

        L.control.browserPrint().addTo(map);

        lay.addTo(map).collapseTree().expandSelected().collapseTree(true);
        L.DomEvent.on(L.DomUtil.get('onlysel'), 'click', function() {
            lay.collapseTree(true).expandSelected(true);
        });

        map.on("browser-print-start", function(e){
            /*on print start we already have a print map and we can create new control and add it to the print map to be able to print custom information */
            L.control.Legend({
                position: "bottomleft",
                collapsed: false,
                symbolWidth: 15,
                opacity: 1,
                column: 2,
                legends: [{
                    label: "Terkoneksi ATCS",
                    type: "image",
                    url: "/ATCS.png"
                },  {
                    label: "Tidak Terkoneksi ATCS",
                    type: "image",
                    url: "/apill.png"
                }]
            }).addTo(e.printMap);
        });
    </script>
@stop