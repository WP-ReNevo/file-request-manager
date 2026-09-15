/**
 * Entry screen for a brand new File Request: "Start from a template" or
 * "Start from scratch".
 */

import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, Card, CardBody, Spinner, Notice } from '@wordpress/components';
import { restPath } from '../../shared/restNamespace';

export default function StartScreen({ onChooseTemplate, onChooseScratch }) {
	const [templates, setTemplates] = useState([]);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState('');

	useEffect(() => {
		let cancelled = false;

		apiFetch({ path: restPath('/templates') })
			.then((items) => {
				if (!cancelled) {
					setTemplates(items);
				}
			})
			.catch((err) => {
				if (!cancelled) {
					setError(
						(err && err.message) ||
							__(
								'Could not load templates.',
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

	return (
		<div className="frm-start-screen">
			<h1>
				{__('Create a File Request', 'renevo-file-request-manager')}
			</h1>
			<p>
				{__(
					'Start from a template, or build your request from scratch.',
					'renevo-file-request-manager'
				)}
			</p>

			{error ? (
				<Notice status="error" isDismissible={false}>
					{error}
				</Notice>
			) : null}

			<div className="frm-start-screen__actions">
				<Button variant="secondary" onClick={onChooseScratch}>
					{__('Start from scratch', 'renevo-file-request-manager')}
				</Button>
			</div>

			<h2>{__('Templates', 'renevo-file-request-manager')}</h2>

			{loading ? (
				<Spinner />
			) : (
				<div className="frm-start-screen__templates">
					{templates.map((template) => (
						<Card key={template.id} className="frm-template-card">
							<CardBody>
								<h3>{template.title}</h3>
								<p>{template.description}</p>
								<Button
									variant="primary"
									className="frm-template-card__button"
									onClick={() => onChooseTemplate(template)}
								>
									{__(
										'Use this template',
										'renevo-file-request-manager'
									)}
								</Button>
							</CardBody>
						</Card>
					))}
				</div>
			)}
		</div>
	);
}
