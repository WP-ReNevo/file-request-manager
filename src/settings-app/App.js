/**
 * The plugin-wide Settings screen, backed by GET/PUT /settings.
 */

import { Fragment, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	Card,
	CardHeader,
	CardBody,
	TextControl,
	CheckboxControl,
	ToggleControl,
	Button,
	Spinner,
	Notice,
} from '@wordpress/components';
import { restPath } from '../shared/restNamespace';

export default function App() {
	const [settings, setSettings] = useState(null);
	const [fileTypes, setFileTypes] = useState({});
	const [loading, setLoading] = useState(true);
	const [loadError, setLoadError] = useState('');
	const [saving, setSaving] = useState(false);
	const [saveNotice, setSaveNotice] = useState('');
	const [saveError, setSaveError] = useState('');

	useEffect(() => {
		let cancelled = false;

		Promise.all([
			apiFetch({ path: restPath('/settings') }),
			apiFetch({ path: restPath('/file-types') }),
		])
			.then(([settingsResponse, fileTypesResponse]) => {
				if (cancelled) {
					return;
				}
				setSettings(settingsResponse);
				setFileTypes(fileTypesResponse);
			})
			.catch((error) => {
				if (!cancelled) {
					setLoadError(
						(error && error.message) ||
							__(
								'Could not load settings.',
								'renevo-file-request-manager'
							)
					);
				}
			})
			.finally(() => {
				if (!cancelled) {
					setLoading(false);
				}
			});

		return () => {
			cancelled = true;
		};
	}, []);

	const setField = (key, value) =>
		setSettings((prev) => ({ ...prev, [key]: value }));

	const toggleAllowedType = (key, checked) => {
		setSettings((prev) => ({
			...prev,
			default_allowed_types: checked
				? [...prev.default_allowed_types, key]
				: prev.default_allowed_types.filter((type) => type !== key),
		}));
	};

	const handleSave = async () => {
		setSaving(true);
		setSaveError('');
		setSaveNotice('');

		try {
			const response = await apiFetch({
				path: restPath('/settings'),
				method: 'PUT',
				data: settings,
			});
			setSettings(response);
			setSaveNotice(__('Settings saved.', 'renevo-file-request-manager'));
		} catch (error) {
			setSaveError(
				(error && error.message) ||
					__(
						'Could not save settings. Please try again.',
						'renevo-file-request-manager'
					)
			);
		} finally {
			setSaving(false);
		}
	};

	if (loadError) {
		return (
			<div className="frm-settings-app__loading">
				<Notice status="error" isDismissible={false}>
					{loadError}
				</Notice>
			</div>
		);
	}

	if (loading || !settings) {
		return (
			<div className="frm-settings-app__loading">
				<Spinner />
			</div>
		);
	}

	return (
		<Fragment>
			<div className="frm-page-header">
				<div className="frm-page-header__row">
					<div className="frm-page-header__title">
						<h1>
							{__(
								'File Request Settings',
								'renevo-file-request-manager'
							)}
						</h1>
					</div>
					<div className="frm-page-header__actions">
						<Button
							variant="primary"
							onClick={handleSave}
							isBusy={saving}
							disabled={saving}
						>
							{saving
								? __('Saving…', 'renevo-file-request-manager')
								: __(
										'Save changes',
										'renevo-file-request-manager'
									)}
						</Button>
					</div>
				</div>
			</div>

			<div className="frm-settings-app">
				{saveNotice ? (
					<Notice
						status="success"
						onRemove={() => setSaveNotice('')}
						className="frm-settings-app__notice"
					>
						{saveNotice}
					</Notice>
				) : null}
				{saveError ? (
					<Notice
						status="error"
						onRemove={() => setSaveError('')}
						className="frm-settings-app__notice"
					>
						{saveError}
					</Notice>
				) : null}

				<Card>
					<CardHeader>
						<h2>{__('General', 'renevo-file-request-manager')}</h2>
					</CardHeader>
					<CardBody>
						<TextControl
							label={__(
								'Default maximum file size (MB)',
								'renevo-file-request-manager'
							)}
							type="number"
							min={1}
							value={settings.default_max_file_size_mb}
							onChange={(value) =>
								setField(
									'default_max_file_size_mb',
									Number(value) || 1
								)
							}
						/>
						<fieldset className="frm-settings-app__type-picker">
							<legend>
								{__(
									'Default allowed file types',
									'renevo-file-request-manager'
								)}
							</legend>
							<div className="frm-settings-app__type-grid">
								{Object.keys(fileTypes).map((key) => (
									<CheckboxControl
										key={key}
										label={fileTypes[key].label}
										checked={settings.default_allowed_types.includes(
											key
										)}
										onChange={(checked) =>
											toggleAllowedType(key, checked)
										}
									/>
								))}
							</div>
						</fieldset>
						<TextControl
							label={__(
								'Default notification email',
								'renevo-file-request-manager'
							)}
							type="email"
							value={settings.notification_email}
							onChange={(value) =>
								setField('notification_email', value)
							}
						/>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<h2>{__('Privacy', 'renevo-file-request-manager')}</h2>
					</CardHeader>
					<CardBody>
						<ToggleControl
							label={__(
								'Store submitter IP address',
								'renevo-file-request-manager'
							)}
							help={__(
								'Off by default. The IP address is one-way hashed, not stored in plain text — for abuse prevention only.',
								'renevo-file-request-manager'
							)}
							checked={settings.store_ip_hash}
							onChange={(value) =>
								setField('store_ip_hash', value)
							}
						/>
						<TextControl
							label={__(
								'Data retention (days, 0 = keep forever)',
								'renevo-file-request-manager'
							)}
							type="number"
							min={0}
							value={settings.retention_days}
							onChange={(value) =>
								setField('retention_days', Number(value) || 0)
							}
							help={__(
								'Submissions and their files are permanently deleted after this many days.',
								'renevo-file-request-manager'
							)}
						/>
						<ToggleControl
							label={__(
								'Delete all data on uninstall',
								'renevo-file-request-manager'
							)}
							help={__(
								'Off by default. Your requests and submissions are kept unless you enable this.',
								'renevo-file-request-manager'
							)}
							checked={settings.delete_data_on_uninstall}
							onChange={(value) =>
								setField('delete_data_on_uninstall', value)
							}
						/>
					</CardBody>
				</Card>
			</div>
		</Fragment>
	);
}
