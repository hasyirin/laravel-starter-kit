function collectNodes(node, nodes = {}) {
    const names = node.permissions.map((permission) => permission.name);

    for (const child of node.children) {
        collectNodes(child, nodes);
        names.push(...nodes[child.key].names);
    }

    nodes[node.key] = { node, names };

    return nodes;
}

function collectLabels(nodes) {
    const labels = {};

    for (const { node } of Object.values(nodes)) {
        for (const permission of node.permissions) {
            labels[permission.name] = permission.label;
        }
    }

    return labels;
}

function collectGrantingRoles(roles) {
    const grantedBy = {};

    for (const [role, { permissions }] of Object.entries(roles)) {
        for (const name of permissions) {
            grantedBy[name] ??= [];
            grantedBy[name].push(role);
        }
    }

    return grantedBy;
}

function escapeHtml(text) {
    return text.replace(/[&<>"']/g, (character) => `&#${character.charCodeAt(0)};`);
}

// The setup comes from an attribute, so a Livewire update keeps the open nodes and the search. The ticked roles and
// permissions live in the Livewire form, so the tree follows the role checkboxes, and the form's save sends the ticks.
export default function permissionTree() {
    let nodes, labels, roles, grantedBy, locked, text;

    return {
        open: new Set(),
        search: '',
        onlyGranted: false,
        expandAll: false,
        readOnly: true,

        init() {
            const config = JSON.parse(this.$root.dataset.permissionTree);

            ({ roles, locked, text } = config);
            nodes = collectNodes(config.tree);
            labels = collectLabels(nodes);
            grantedBy = collectGrantingRoles(roles);
            this.readOnly = config.readOnly;

            const grantedKeys = Object.keys(nodes).filter((key) => nodes[key].names.some((name) => this.isGranted(name)));

            this.open = new Set(grantedKeys);
        },

        get query() {
            return this.search.trim().toLowerCase();
        },

        isTicked(name) {
            return this.$wire.form.permissions.includes(name);
        },

        roleOf(name) {
            return grantedBy[name]?.find((role) => this.$wire.form.roles.includes(role));
        },

        fromText(name) {
            const role = this.roleOf(name);

            if (role === undefined) {
                return '';
            }

            return text.from.replace(':role', roles[role].label);
        },

        isGranted(name) {
            return this.isTicked(name) || this.roleOf(name) !== undefined;
        },

        isLocked(name) {
            return this.readOnly || locked.includes(name);
        },

        chipState(name) {
            if (locked.includes(name)) {
                return 'lock';
            }

            if (this.isTicked(name)) {
                return 'on';
            }

            if (this.roleOf(name) !== undefined) {
                return 'role';
            }

            return 'off';
        },

        toggle(name) {
            if (this.isLocked(name)) {
                return;
            }

            const permissions = this.$wire.form.permissions;

            if (permissions.includes(name)) {
                this.$wire.form.permissions = permissions.filter((ticked) => ticked !== name);

                return;
            }

            this.$wire.form.permissions = [...permissions, name];
        },

        resourceState(key) {
            const permissions = nodes[key].node.permissions;
            const ticked = permissions.filter((permission) => this.isTicked(permission.name)).length;

            if (ticked === 0) {
                return 'off';
            }

            if (ticked === permissions.length) {
                return 'on';
            }

            return 'part';
        },

        resourceChecked(key) {
            const state = this.resourceState(key);

            if (state === 'part') {
                return 'mixed';
            }

            return String(state === 'on');
        },

        toggleResource(key) {
            if (this.readOnly) {
                return;
            }

            const editable = nodes[key].node.permissions.map((permission) => permission.name).filter((name) => !this.isLocked(name));
            const others = this.$wire.form.permissions.filter((name) => !editable.includes(name));

            if (this.resourceState(key) === 'on') {
                this.$wire.form.permissions = others;

                return;
            }

            this.$wire.form.permissions = [...others, ...editable];
        },

        countText(key) {
            const names = nodes[key].names;
            const granted = names.filter((name) => this.isGranted(name)).length;

            return text.count.replace(':granted', granted).replace(':total', names.length);
        },

        isShown(name) {
            if (this.onlyGranted && !this.isGranted(name)) {
                return false;
            }

            if (this.query === '') {
                return true;
            }

            return labels[name].toLowerCase().includes(this.query) || name.toLowerCase().includes(this.query);
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

                return;
            }

            this.open.add(key);
        },

        highlighted(name) {
            const label = labels[name];

            if (this.query === '') {
                return escapeHtml(label);
            }

            const at = label.toLowerCase().indexOf(this.query);

            if (at === -1) {
                return escapeHtml(label);
            }

            const end = at + this.query.length;

            return `${escapeHtml(label.slice(0, at))}<mark>${escapeHtml(label.slice(at, end))}</mark>${escapeHtml(label.slice(end))}`;
        },
    };
}
