/**
 * Wraps the classic WordPress editor (TinyMCE + Quicktags, via
 * wp.editor.initialize()) around a plain textarea. Uncontrolled by design —
 * TinyMCE/Quicktags own the DOM node once initialized, so `onChange` only
 * reports content upward, it never feeds a `value` back in.
 */

import { useEffect, useRef } from '@wordpress/element';

export default function DescriptionEditor({ id, initialValue, onChange }) {
	const onChangeRef = useRef(onChange);
	onChangeRef.current = onChange;

	useEffect(() => {
		if (!window.wp || !window.wp.editor) {
			return undefined;
		}

		window.wp.editor.initialize(id, {
			tinymce: {
				wpautop: true,
				plugins: 'lists,link,paste,wordpress,wplink,charmap,hr',
				toolbar1:
					'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink,undo,redo',
				setup(editor) {
					editor.on('change keyup undo redo', () => {
						onChangeRef.current(editor.getContent());
					});
				},
			},
			quicktags: true,
			mediaButtons: false,
		});

		return () => {
			if (window.wp && window.wp.editor) {
				window.wp.editor.remove(id);
			}
		};
	}, [id]);

	return (
		<textarea
			id={id}
			defaultValue={initialValue}
			onChange={(event) => onChange(event.target.value)}
			rows={10}
		/>
	);
}
