/**
 * The "Create/Edit File Request" admin app.
 */

import {
	useEffect,
	useReducer,
	useState,
	useCallback,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Spinner, Notice } from '@wordpress/components';
import { editorReducer } from './state/editorReducer';
import { Request } from './state/Request';
import { restPath } from '../shared/restNamespace';
import StartScreen from './components/StartScreen';
import EditorScreen from './components/EditorScreen';

export default function App({ config }) {
	const { requestsUrl, adminEmail, siteName, faviconUrl } = config;
	// wp_localize_script() casts every value to a string, so config.requestId
	// arrives as "0" (truthy in JS) for a brand new request — coerce to a number.
	const requestId = Number(config.requestId) || 0;

	// Stage: 'loading' (initial fetch for an existing request), 'choose'
	// (template-vs-scratch entry screen for a brand new request), or 'edit'.
	const [stage, setStage] = useState(requestId ? 'loading' : 'choose');
	const [loadError, setLoadError] = useState('');
	const [formDesignPresets, setFormDesignPresets] = useState({});
	const [formLayouts, setFormLayouts] = useState({});
	const [state, dispatch] = useReducer(
		editorReducer,
		Request.blank(adminEmail, siteName)
	);

	useEffect(() => {
		apiFetch({ path: restPath('/form-design-presets') })
			.then(setFormDesignPresets)
			.catch(() => {});
		apiFetch({ path: restPath('/form-layouts') })
			.then(setFormLayouts)
			.catch(() => {});
	}, []);

	useEffect(() => {
		if (!requestId) {
			return;
		}

		let cancelled = false;

		apiFetch({ path: restPath(`/requests/${requestId}`) })
			.then((request) => {
				if (cancelled) {
					return;
				}
				dispatch({
					type: 'REPLACE',
					state: Request.fromServer(request),
				});
				setStage('edit');
			})
			.catch((error) => {
				if (!cancelled) {
					setLoadError(
						(error && error.message) ||
							__(
								"We couldn't load this File Request.",
								'renevo-file-request-manager'
							)
					);
				}
			});

		return () => {
			cancelled = true;
		};
	}, [requestId]);

	const handleChooseTemplate = useCallback(
		(template) => {
			dispatch({
				type: 'REPLACE',
				state: Request.fromTemplate(template, adminEmail, siteName),
			});
			setStage('edit');
		},
		[adminEmail, siteName]
	);

	const handleChooseScratch = useCallback(() => {
		dispatch({
			type: 'REPLACE',
			state: Request.blank(adminEmail, siteName),
		});
		setStage('edit');
	}, [adminEmail, siteName]);

	if (loadError) {
		return (
			<div className="frm-editor-app__loading">
				<Notice status="error" isDismissible={false}>
					{loadError}
				</Notice>
			</div>
		);
	}

	if (stage === 'loading') {
		return (
			<div className="frm-editor-app__loading">
				<Spinner />
			</div>
		);
	}

	if (stage === 'choose') {
		return (
			<StartScreen
				onChooseTemplate={handleChooseTemplate}
				onChooseScratch={handleChooseScratch}
			/>
		);
	}

	return (
		<EditorScreen
			state={state}
			dispatch={dispatch}
			requestsUrl={requestsUrl}
			siteName={siteName}
			faviconUrl={faviconUrl}
			formDesignPresets={formDesignPresets}
			formLayouts={formLayouts}
		/>
	);
}
