// Imported once by both frontend-app and editor-app entry points so the
// built-in layouts (and the shared components a Pro layout composes with)
// are registered before first render. See src/shared/layoutRegistry.js.

import { registerLayout } from '../layoutRegistry';
import RequestHeader from '../RequestHeader';
import RequestedFileCard from '../RequestedFileCard';
import ContactFields from '../ContactFields';
import SubmitBar from '../SubmitBar';
import StackedLayout from './StackedLayout';
import GridLayout from './GridLayout';
import EditorialLayout from './EditorialLayout';

registerLayout('stacked', StackedLayout);
registerLayout('grid', GridLayout);
registerLayout('editorial', EditorialLayout);

const w = typeof window !== 'undefined' ? window : {};
w.renevo = w.renevo || {};
w.renevo.components = {
	RequestHeader,
	RequestedFileCard,
	ContactFields,
	SubmitBar,
};
