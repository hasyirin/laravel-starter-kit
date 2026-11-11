export default function letterRows(key) {
    return {
        addRow(blank) {
            this.$wire.$set(key, [...(this.$wire.$get(key) ?? []), blank]);
        },

        removeRow(position) {
            this.$wire.$set(
                key,
                this.$wire.$get(key).filter((_, index) => index !== position),
            );
        },
    };
}
