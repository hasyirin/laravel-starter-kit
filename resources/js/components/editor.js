// Flux links the label and the description to ui-editor, but screen readers read the textbox inside it.
const name = (editor) => {
    const textbox = editor.querySelector('.ProseMirror');

    for (const attribute of ['aria-labelledby', 'aria-describedby']) {
        if (textbox && editor.hasAttribute(attribute)) {
            textbox.setAttribute(attribute, editor.getAttribute(attribute));
        }
    }
};

// Flux's editor script can create an editor before this file runs, so name the ones that exist and the ones that come later.
export default function nameEditors() {
    document.querySelectorAll('ui-editor').forEach(name);
    document.addEventListener('flux:editor:ready', (event) => name(event.target));
}
