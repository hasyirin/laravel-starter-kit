function collectNodes(node, nodes = {}) {
    nodes[node.key] = {
        node,
        names: [...node.permissions.map((permission) => permission.name), ...node.children.flatMap((child) => collectNodes(child, nodes)[child.key].names)],
    };

    return nodes;
}

function escapeHtml(text) {
    return text.replace(/[&<>"']/g, (character) => `&#${character.charCodeAt(0)};`);
}

// The data comes from an attribute, so a Livewire update that changes it keeps the state of the tree.
export default function permissionTree() {
    let nodes, labels, fromRoles, locked, text;

    return {
        saved: new Set(),
        ticked: new Set(),
        open: new Set(),
        search: '',
        onlyGranted: false,
        expandAll: false,
        readOnly: true,

        init() {
            const config = JSON.parse(this.$root.dataset.permissionTree);

            ({ fromRoles, locked, text } = config);
            nodes = collectNodes(config.tree);
            labels = Object.fromEntries(Object.values(nodes).flatMap(({ node }) => node.permissions.map((permission) => [permission.name, permission.label])));
            this.saved = new Set(config.direct);
            this.ticked = new Set(config.direct);
            this.readOnly = config.readOnly;
            this.open = new Set(Object.keys(nodes).filter((key) => nodes[key].names.some((name) => this.isGranted(name))));
        },

        get query() {
            return this.search.trim().toLowerCase();
        },

        get changes() {
            return [...this.ticked].filter((name) => !this.saved.has(name)).length + [...this.saved].filter((name) => !this.ticked.has(name)).length;
        },

        get changesText() {
            return (this.changes === 1 ? text.one : text.other).replace(':count', this.changes);
        },

        isGranted(name) {
            return this.ticked.has(name) || name in fromRoles;
        },

        isLocked(name) {
            return this.readOnly || name in fromRoles || locked.includes(name);
        },

        chipState(name) {
            if (name in fromRoles || locked.includes(name)) {
                return 'lock';
            }

            return this.ticked.has(name) ? 'on' : 'off';
        },

        toggle(name) {
            if (this.isLocked(name)) {
                return;
            }

            if (this.ticked.has(name)) {
                this.ticked.delete(name);
            } else {
                this.ticked.add(name);
            }
        },

        resourceState(key) {
            const granted = nodes[key].node.permissions.filter((permission) => this.isGranted(permission.name)).length;

            return granted === 0 ? 'off' : granted === nodes[key].node.permissions.length ? 'on' : 'part';
        },

        toggleResource(key) {
            if (this.readOnly) {
                return;
            }

            const editable = nodes[key].node.permissions.map((permission) => permission.name).filter((name) => !this.isLocked(name));
            const tick = this.resourceState(key) !== 'on';

            editable.forEach((name) => (tick ? this.ticked.add(name) : this.ticked.delete(name)));
        },

        countText(key) {
            const names = nodes[key].names;

            return text.count.replace(':granted', names.filter((name) => this.isGranted(name)).length).replace(':total', names.length);
        },

        isShown(name) {
            if (this.onlyGranted && !this.isGranted(name)) {
                return false;
            }

            return this.query === '' || labels[name].toLowerCase().includes(this.query) || name.toLowerCase().includes(this.query);
        },

        isNodeShown(key) {
            return nodes[key].names.some((name) => this.isShown(name));
        },

        isOpen(key) {
            return this.expandAll || this.query !== '' || this.open.has(key);
        },

        toggleOpen(key) {
            if (this.open.has(key)) {
                this.open.delete(key);
            } else {
                this.open.add(key);
            }
        },

        highlighted(name) {
            const label = labels[name];
            const at = this.query === '' ? -1 : label.toLowerCase().indexOf(this.query);

            if (at === -1) {
                return escapeHtml(label);
            }

            const end = at + this.query.length;

            return `${escapeHtml(label.slice(0, at))}<mark>${escapeHtml(label.slice(at, end))}</mark>${escapeHtml(label.slice(end))}`;
        },

        cancel() {
            this.ticked = new Set(this.saved);
        },

        async save() {
            const saved = await this.$wire.save([...this.ticked]);

            if (Array.isArray(saved)) {
                this.saved = new Set(saved);
                this.ticked = new Set(saved);
            }
        },
    };
}
