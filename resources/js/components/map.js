// Leaflet is large, so only a page with a map loads it.
async function leaflet() {
    const [L, icon, retinaIcon, shadow] = await Promise.all([
        import('leaflet/dist/leaflet-src.esm.js'),
        import('leaflet/dist/images/marker-icon.png'),
        import('leaflet/dist/images/marker-icon-2x.png'),
        import('leaflet/dist/images/marker-shadow.png'),
        import('leaflet/dist/leaflet.css'),
    ]);

    // Leaflet finds its marker images through a CSS path, which the bundle breaks. An empty path makes it use these URLs as they are.
    L.Icon.Default.imagePath = '';
    L.Icon.Default.mergeOptions({ iconUrl: icon.default, iconRetinaUrl: retinaIcon.default, shadowUrl: shadow.default });

    return L;
}

function coordinate(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const number = Number(value);

    return Number.isFinite(number) ? number : null;
}

function round(value) {
    return Number(value.toFixed(6));
}

export default function map() {
    let L = null;
    let canvas = null;
    let marker = null;
    let observer = null;
    let mutations = null;
    let shown = false;
    let destroyed = false;

    return {
        options: null,

        async init() {
            this.options = JSON.parse(this.$root.dataset.map);
            L = await leaflet();

            // Alpine can destroy the component while Leaflet loads, for example when the user leaves the page.
            if (destroyed) {
                return;
            }

            const point = this.point();

            canvas = L.map(this.$refs.canvas, { center: point ?? this.options.center, zoom: point ? this.options.pointZoom : this.options.zoom });

            const layers = this.options.layers.map((layer) =>
                L.tileLayer(layer.url, { attribution: layer.attribution, maxZoom: layer.maxZoom, subdomains: layer.subdomains ?? 'abc' }),
            );

            layers[0].addTo(canvas);

            if (layers.length > 1) {
                L.control.layers(Object.fromEntries(this.options.layers.map((layer, index) => [layer.label, layers[index]]))).addTo(canvas);
            }

            this.show();
            this.follow();
            canvas.on('click', (event) => this.place(event.latlng));
        },

        destroy() {
            destroyed = true;
            observer?.disconnect();
            mutations?.disconnect();
            canvas?.remove();
            canvas = null;
            marker = null;
        },

        point() {
            const { latitudeModel, longitudeModel } = this.options;
            const latitude = coordinate(latitudeModel ? this.$wire.$get(latitudeModel) : this.options.latitude);
            const longitude = coordinate(longitudeModel ? this.$wire.$get(longitudeModel) : this.options.longitude);

            if (latitude === null || longitude === null) {
                return null;
            }

            return [latitude, longitude];
        },

        // Moves the marker to the bound values, and shows it when it is out of view.
        show() {
            const point = this.point();

            if (!point) {
                marker?.remove();
                marker = null;

                return;
            }

            if (marker) {
                marker.setLatLng(point);
            } else {
                marker = L.marker(point, { autoPan: true }).addTo(canvas);
                marker.on('dragend', () => this.place(marker.getLatLng()));
            }

            if (this.options.clickable) {
                marker.dragging.enable();
            } else {
                marker.dragging.disable();
            }

            if (!canvas.getBounds().contains(point)) {
                canvas.setView(point, Math.max(canvas.getZoom(), this.options.pointZoom));
            }
        },

        place(latlng) {
            if (!this.options.clickable) {
                return;
            }

            const { lat, lng } = latlng.wrap();

            // The third argument keeps the change local until the next request, like a plain wire:model.
            this.$wire.$set(this.options.latitudeModel, round(lat), false);
            this.$wire.$set(this.options.longitudeModel, round(lng), false);
        },

        follow() {
            const { latitudeModel, longitudeModel } = this.options;

            if (latitudeModel) {
                this.$wire.$watch(latitudeModel, () => this.show());
                this.$wire.$watch(longitudeModel, () => this.show());
            }

            // A folded card, a closed tab, or a resized window changes the size of the map. Leaflet then needs a new measure.
            observer = new ResizeObserver(([entry]) => this.resize(entry.contentRect));
            observer.observe(this.$refs.canvas);

            // A Livewire update can change the options, for example when the card around the map becomes disabled.
            mutations = new MutationObserver(() => this.sync());
            mutations.observe(this.$root, { attributes: true, attributeFilter: ['data-map'] });
        },

        sync() {
            this.options = JSON.parse(this.$root.dataset.map);
            this.show();
        },

        resize(size) {
            const wasShown = shown;

            shown = size.width > 0 && size.height > 0;
            canvas.invalidateSize({ pan: false });

            // Leaflet measures a hidden map as empty, so it sets the view again when the map shows.
            if (wasShown || !shown) {
                return;
            }

            const point = this.point();

            canvas.setView(point ?? this.options.center, point ? this.options.pointZoom : this.options.zoom, { animate: false });
        },
    };
}
