<?php 

/**
* Load the base class
*/
class Endo_Generate_PDF {
	
	function __construct()	{
		
	}

	/**
	 * Kick it off
	 * 
	 */
	public function run() {

		self::setup_constants();

		// add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action('admin_menu', array( $this, 'create_admin_menu') );
		add_action('admin_init', array( $this, 'create_settings' ) );
		add_action('admin_init', array( $this, 'process_requests' ), 15 );
	}

	public function load_scripts() {

		wp_enqueue_script( 'bpopup', ENDO_GENERATE_PDF_URL . 'js/bpopup.js' );

	}

	/**
	 * Setup plugin constants.
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	private function setup_constants() {

		// Plugin version.
		if ( ! defined( 'ENDO_GENERATE_PDF_VERSION' ) ) {
			define( 'ENDO_GENERATE_PDF_VERSION', '1.0.0' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'ENDO_GENERATE_PDF_PLUGIN_DIR' ) ) {
			define( 'ENDO_GENERATE_PDF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Folder URL.
		if ( ! defined( 'ENDO_GENERATE_PDF_PLUGIN_URL' ) ) {
			define( 'ENDO_GENERATE_PDF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'ENDO_GENERATE_PDF_PLUGIN_FILE' ) ) {
			define( 'ENDO_GENERATE_PDF_PLUGIN_FILE', __FILE__ );
		}

	}

	// generate pdf from section id
	public function process_requests() {

		if ( !is_admin() && !current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( !isset( $_POST['option_page'] ) && $_POST['option_page'] != 'endopdf_options' ) {
			return;
		}

		if (  ! isset( $_POST['endo_pdf_nonce'] )  || ! wp_verify_nonce( $_POST['endo_pdf_nonce'], 'generate_transcript_pdf' ) ) {
			return;
		}

		$section_id = $_POST['endopdf_options']['section'];

		$html = $this->display_cover_page( $section_id );
		$html .= $this->display_section_questions( $section_id );

		$paper_size = 'A4';

		$mpdf = new mPDF('utf-8', $paper_size); 
		$mpdf->setFooter('<p style="text-align: center;">Confidential and Copyright AnswerOn, Inc</p> {PAGENO}');
		$mpdf->SetHTMLHeader('<div><img style="float: left; margin-bottom: 20px;" width="200" src="' . ENDO_GENERATE_PDF_PLUGIN_URL . '/images/AnswerOn_logo.jpg"><img style="float: right; margin-bottom: 20px;" width="100" src="' . ENDO_GENERATE_PDF_PLUGIN_URL . '/images/parle-logo.jpg"></div>');
		$mpdf->WriteHTML($html);
		$mpdf->Output();
		exit;
				

	}

	public function create_admin_menu() {

		add_submenu_page('edit.php?post_type=forum', 'Generate Transcript', 'Generate Transcript', 'edit_posts', 'endopdf-' . basename(__FILE__), array( $this, 'section_transcript_page') );

	}

	public function section_transcript_page() {

		$options = get_option('endopdf_options');
		$section = $options['section'];

		?>

		<div class="wrap">

	        <h2>Generate Transcript for Section</h2>
	        <p>Choose a section below to export.</p>

	      	<form method="post" action="edit.php?post_type=forum&page=endopdf-class-endo-generate-pdf.php">
	            <?php settings_fields( 'endopdf_options' ); ?>
            	<?php do_settings_sections( 'endopdf_opt' ); ?>      
            	<?php wp_nonce_field( 'generate_transcript_pdf', 'endo_pdf_nonce' ); ?>
            	<?php submit_button('Generate PDF'); ?>
        
	        </form>

	    </div>

    	<?php 

	}

	

	public function create_settings() {

		register_setting(
	        'endopdf_options',
	        'endopdf_options',
	        array( $this, 'endopdf_validate_options' )
	    );

		add_settings_section(
	        'endopdf_general',        
	        '',                 
	        '',
	        'endopdf_opt'                           
	    );

	    // add_settings_field(
	    //     'endopdf_file',                  
	    //     'File Name',                         
	    //     array( $this, 'endopdf_text_input' ),  
	    //     'endopdf_opt',                         
	    //     'endopdf_general'       
	    // );

	    add_settings_field(
	        'endopdf_section',                  
	        'Section',                         
	        array( $this, 'endopdf_section_input' ),  
	        'endopdf_opt',                         
	        'endopdf_general'       
	    );

	}

	public function endopdf_text_input() {

		$options = wp_parse_args( get_option( 'endopdf_options' ), array('csv_file' => ''));
		$file = $options['csv_file'];
	
		?>
		<input type="text" id="enimp_csv_file" name="enimp_options[csv_file]" value="<?php echo esc_url( $file ); ?>" />
	    <input id="upload_csv_button" type="button" class="button" value="<?php _e( 'Upload CSV', 'endo-import' ); ?>" />
	        <span class="description"><?php _e('Upload a csv file.', 'endo-import' ); ?></span>
	    <?php 
	}

	public function endopdf_section_input() {

		$options = wp_parse_args( get_option( 'endopdf_options' ), array('section' => ''));
		$section = $options['section'];
		
		$project_parents = array();

		?>
		
		<select name="endopdf_options[section]">
			<option value="">-</option>
			<?php 

				// create option groups of parent projects and their children section
				$args = array(
					'posts_per_page' => 999,
					'post_type'	=> 'forum'
				);

				$projects = get_posts( $args );

				foreach( $projects as $project ) {

					$parent_id = wp_get_post_parent_id( $project->ID );

					if ( !$parent_id ) {

						$project_parents[] = $project->ID;

					}

				}


				foreach( $project_parents as $project_parent_id ) {

					$args = array(
						'post_parent'	=> $project_parent_id,
						'post_type'		=> 'forum',
						'numberposts'	=> 999,
						'order'	=> 'ASC',
						'orderby'	=> 'title'
					);

					$children_projects = get_children( $args );

					echo '<optgroup label="' . get_the_title( $project_parent_id ) . '">';

					foreach( $children_projects as $child_project ) {
						echo '<option value="' . $child_project->ID . '">' . $child_project->post_title . '</option>';
					}

					echo '</optgroup>';

				}
			?>
		</select>
	
	       
	    <?php 
	}

	public function endopdf_validate_options( $input ) {
	    return $input;
	}

	public function display_cover_page( $section_id ) {

		$output = '';

		// $output .= '<p style="text-align: center; padding-top: 40px;"><img src="' . ENDO_GENERATE_PDF_PLUGIN_URL . '/images/AnswerOn_logo.jpg"></p>';
		$output .= '<h1 style="text-align: center; padding-top: 40px;">' . answeron_get_project_title( $section_id ) . ' </h1>';
		$output .= '<h2 style="text-align: center; ">' . str_replace( ':', '', get_the_title( $section_id ) ) . '</h2>';

		$output .= '<pagebreak>';

		return $output;

	}

	public function display_section_questions( $section_id ) {

		$output = '';

		$question_titles = get_question_titles( $section_id );
		$question_ids = get_question_ids( $section_id );

		foreach( $question_ids as $question_id ) {

			// $output .= '<h3 style="color: #1C4489; padding-top: 40px;">' . get_the_title( $question_id ) . '</h3>';
			$output .= '<p style="padding-top: 20px;">&nbsp;</p>';
			$question = get_post_meta($question_id);

    		$question_content = unserialize($question['question'][0]);

    		for($i = 0; $i < count($question_content); $i++ ) {

		    	switch($question_content[$i]['question_type_' . $i]) {
					case 'text':
						// $output .= 'text question: ';
						$output .= '<h3 style="color: #1C4489; padding-top: 20px;">' . $question_content[$i]['the_question_' . $i] . '</h3>';
						// ao_question_text($question_content[$i], $i);
					break;
					case 'choice':
						// $output .= 'choice question: ';
						$output .= '<h3 style="color: #1C4489; padding-top: 20px;">' . $question_content[$i]['the_question_' . $i] . '</h3>';
						$question_choices = implode(",",$question_content[$i]['_question_choice_array_'.$i]);
					
						$output .= '<p>' . $question_choices . '</p>';
						// ao_question_choice($question_content[$i], $i);
					break;
					case 'rating':
						// $output .= 'rating question: ';
						$rates_array = $question_content[$i]['_question_rating_array_'.$i];
						$rates_array = array_reverse( $rates_array );
						$question_rates = implode(",", $rates_array );
						$output .= '<h3 style="color: #1C4489; padding-top: 20px;">' . $question_content[$i]['the_question_' . $i] . '</h3>';
						$output .= '<p>' . $question_rates . '</p>';
						//ao_question_rating($question_content[$i], $i);
					break;
					case 'ranking':
						// $output .= 'ranking question: ';
						$output .= '<h3 style="color: #1C4489; padding-top: 20px;">' . $question_content[$i]['the_question_' . $i] . '</h3>';
						// ao_question_ranking($question_content[$i], $i);
					break;
					case 'notice':
						// $output .= 'notice question: ';
						$output .= '<h3 style="color: #1C4489; padding-top: 20px;">' . $question_content[$i]['the_question_' . $i] . '</h3>';
						// ao_question_notice($question_content[$i], $i);
					break;

				}

		    }

			$args = array(
				'posts_per_page'	=> 200,
				'post_type'	=> 'reply', 
				'meta_key'	=> '_bbp_topic_id',
				'meta_value'	=> $question_id,
				'order'		=> 'ASC'
			);

			$replies = get_posts( $args );

			foreach ( $replies as $reply ) {

				$author_id = $reply->post_author;
				$author_info = get_userdata( $author_id );

				$output .= '<p>' . $author_info->display_name . '<br>' . $reply->post_content . '</p>';

			}

			$output .= '<pagebreak>';


			// get all replies of this question

		}

		return $output;

	}
}