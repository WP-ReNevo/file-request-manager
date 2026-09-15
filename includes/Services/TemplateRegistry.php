<?php
/**
 * Registry of request templates offered on "Start from a template".
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry of built-in request templates.
 */
class TemplateRegistry {

	/**
	 * Gets every available template.
	 *
	 * @return array<int, array{id: string, title: string, description: string, requested_files: array, contact_fields: array}>
	 */
	public function all() {
		$templates = array(
			$this->general_document_request(),
			$this->company_registration(),
			$this->client_onboarding(),
			$this->tax_documents(),
			$this->website_content(),
			$this->job_application(),
			$this->property_documents(),
			$this->photo_submission(),
		);

		/**
		 * Filters the list of request templates offered when creating a new request.
		 *
		 * @param array $templates List of template definitions.
		 */
		return apply_filters( 'renevo_request_templates', $templates );
	}

	/**
	 * Finds one template by ID.
	 *
	 * @param string $id Template ID.
	 * @return array|null
	 */
	public function find( $id ) {
		foreach ( $this->all() as $template ) {
			if ( $template['id'] === $id ) {
				return $template;
			}
		}

		return null;
	}

	/**
	 * Builds one requested-file definition with a fresh key.
	 *
	 * @param string   $title         Title.
	 * @param string   $description   Description.
	 * @param bool     $required      Whether required.
	 * @param string[] $allowed_types Allowed type keys.
	 * @param int      $max_size_mb   Max size in MB.
	 * @param int      $max_files     Max file count.
	 * @return array
	 */
	private function file( $title, $description, $required, array $allowed_types, $max_size_mb = 10, $max_files = 1 ) {
		return array(
			'key'           => wp_generate_uuid4(),
			'title'         => $title,
			'description'   => $description,
			'required'      => $required,
			'allowed_types' => $allowed_types,
			'max_size_mb'   => $max_size_mb,
			'max_files'     => $max_files,
		);
	}

	/**
	 * "General document request" template.
	 *
	 * @return array
	 */
	private function general_document_request() {
		return array(
			'id'              => 'general-document-request',
			'title'           => __( 'General document request', 'renevo-file-request-manager' ),
			'description'     => __( 'A simple starting point for requesting one or more documents.', 'renevo-file-request-manager' ),
			'requested_files' => array(
				$this->file( __( 'Document', 'renevo-file-request-manager' ), __( 'Please upload a clear scan or photo.', 'renevo-file-request-manager' ), true, array( 'pdf', 'jpg', 'png' ) ),
			),
		);
	}

	/**
	 * "Company registration" template.
	 *
	 * @return array
	 */
	private function company_registration() {
		return array(
			'id'              => 'company-registration',
			'title'           => __( 'Company registration', 'renevo-file-request-manager' ),
			'description'     => __( 'Documents required to start your company.', 'renevo-file-request-manager' ),
			'requested_files' => array(
				$this->file( __( 'Identity document', 'renevo-file-request-manager' ), __( 'Please upload a clear scan or photo of your identity document.', 'renevo-file-request-manager' ), true, array( 'pdf', 'jpg', 'png' ) ),
				$this->file( __( 'Proof of address', 'renevo-file-request-manager' ), __( 'A recent utility bill or bank statement showing your address.', 'renevo-file-request-manager' ), true, array( 'pdf', 'jpg', 'png' ) ),
				$this->file( __( 'Signed contract', 'renevo-file-request-manager' ), '', true, array( 'pdf' ) ),
				$this->file( __( 'Company documents', 'renevo-file-request-manager' ), '', false, array( 'pdf', 'doc', 'docx' ), 10, 10 ),
			),
		);
	}

	/**
	 * "Client onboarding" template.
	 *
	 * @return array
	 */
	private function client_onboarding() {
		return array(
			'id'              => 'client-onboarding',
			'title'           => __( 'Client onboarding', 'renevo-file-request-manager' ),
			'description'     => __( 'Everything needed to get a new client set up.', 'renevo-file-request-manager' ),
			'requested_files' => array(
				$this->file( __( 'Identity document', 'renevo-file-request-manager' ), '', true, array( 'pdf', 'jpg', 'png' ) ),
				$this->file( __( 'Signed agreement', 'renevo-file-request-manager' ), '', true, array( 'pdf' ) ),
			),
		);
	}

	/**
	 * "Tax documents" template.
	 *
	 * @return array
	 */
	private function tax_documents() {
		return array(
			'id'              => 'tax-documents',
			'title'           => __( 'Tax documents', 'renevo-file-request-manager' ),
			'description'     => __( 'Documents for your monthly accounting.', 'renevo-file-request-manager' ),
			'requested_files' => array(
				$this->file( __( 'Invoices', 'renevo-file-request-manager' ), '', true, array( 'pdf', 'jpg', 'png' ), 10, 50 ),
				$this->file( __( 'Bank statements', 'renevo-file-request-manager' ), '', true, array( 'pdf' ), 10, 12 ),
				$this->file( __( 'Receipts', 'renevo-file-request-manager' ), '', false, array( 'pdf', 'jpg', 'png' ), 10, 50 ),
			),
		);
	}

	/**
	 * "Website content" template.
	 *
	 * @return array
	 */
	private function website_content() {
		return array(
			'id'              => 'website-content',
			'title'           => __( 'Website content', 'renevo-file-request-manager' ),
			'description'     => __( 'Content needed for your website.', 'renevo-file-request-manager' ),
			'requested_files' => array(
				$this->file( __( 'Logo', 'renevo-file-request-manager' ), '', true, array( 'png', 'jpg' ) ),
				$this->file( __( 'Company photos', 'renevo-file-request-manager' ), '', false, array( 'jpg', 'png', 'webp' ), 10, 20 ),
				$this->file( __( 'Team photos', 'renevo-file-request-manager' ), '', false, array( 'jpg', 'png', 'webp' ), 10, 20 ),
				$this->file( __( 'Brand assets', 'renevo-file-request-manager' ), '', false, array( 'zip', 'pdf', 'png' ), 20, 5 ),
			),
		);
	}

	/**
	 * "Job application" template.
	 *
	 * @return array
	 */
	private function job_application() {
		return array(
			'id'              => 'job-application',
			'title'           => __( 'Job application', 'renevo-file-request-manager' ),
			'description'     => __( 'Documents required to apply for a position.', 'renevo-file-request-manager' ),
			'requested_files' => array(
				$this->file( __( 'Resume / CV', 'renevo-file-request-manager' ), '', true, array( 'pdf', 'doc', 'docx' ) ),
				$this->file( __( 'Cover letter', 'renevo-file-request-manager' ), '', false, array( 'pdf', 'doc', 'docx' ) ),
			),
		);
	}

	/**
	 * "Property documents" template.
	 *
	 * @return array
	 */
	private function property_documents() {
		return array(
			'id'              => 'property-documents',
			'title'           => __( 'Property documents', 'renevo-file-request-manager' ),
			'description'     => __( 'Documents required for your property.', 'renevo-file-request-manager' ),
			'requested_files' => array(
				$this->file( __( 'Identity document', 'renevo-file-request-manager' ), '', true, array( 'pdf', 'jpg', 'png' ) ),
				$this->file( __( 'Proof of address', 'renevo-file-request-manager' ), '', true, array( 'pdf', 'jpg', 'png' ) ),
				$this->file( __( 'Property documents', 'renevo-file-request-manager' ), '', true, array( 'pdf' ), 10, 10 ),
			),
		);
	}

	/**
	 * "Photo submission" template.
	 *
	 * @return array
	 */
	private function photo_submission() {
		return array(
			'id'              => 'photo-submission',
			'title'           => __( 'Photo submission', 'renevo-file-request-manager' ),
			'description'     => __( 'Files required for your project.', 'renevo-file-request-manager' ),
			'requested_files' => array(
				$this->file( __( 'Original photos', 'renevo-file-request-manager' ), '', true, array( 'jpg', 'png' ), 25, 100 ),
				$this->file( __( 'References', 'renevo-file-request-manager' ), '', false, array( 'jpg', 'png', 'pdf' ), 15, 20 ),
			),
		);
	}
}
