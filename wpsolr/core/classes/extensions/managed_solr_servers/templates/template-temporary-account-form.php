<?php
/**
 * Managed Solr server temporary free account
 */

use wpsolr\core\classes\extensions\managed_solr_servers\OptionManagedSolrServer;

?>

<?php
global $license_manager;
?>

<div class="wrapper">

    <form method="POST" id="form_temporary_index">
        <input type="hidden" name="wpsolr_action" value="wpsolr_admin_action_form_temporary_index"/>
        <input type="hidden" name="data-sitekey" value=""/>
        <input type="hidden" name="zdata-stoken" value=""/>

		<?php if ( empty( $google_recaptcha_token ) ) { ?>
        <input type="button" class="button-secondary wpsolr_collapser"
               value="Click to generate a free index to test WPSOLR"/>
        <div class="wpsolrc-form-row <?php echo( empty( $response_error ) ? 'wpsolr_collapsed' : '' ); ?>">
			<?php } else { ?>
            <div class="wpsolrc-form-row">
				<?php } ?>

                <div class='col_left' style='width: 10%;'>

                    Select your test index type
                </div>

                <div class='col_right' style='width: 80%;'>
                    <ul>
						<?php
						foreach ( OptionManagedSolrServer::get_managed_solr_services() as $list_managed_solr_service_id => $managed_solr_service ) {
							printf( "<li><input type='radio' name='managed_solr_service_id' value='%s' %s/>%s</li>",
								$list_managed_solr_service_id,
								checked( $list_managed_solr_service_id, $managed_solr_service_id, false ),
								$managed_solr_service[ OptionManagedSolrServer::MANAGED_SOLR_SERVICE_LABEL ]
							);
						}
						?>
                    </ul>

					<?php
					if ( empty( $google_recaptcha_token ) ) {
						?>
                    <input name="submit_button_form_temporary_index_select_managed_solr_service_id" type="submit"
                           class="button-primary "
                           value="Create a free test index"/>
					<?php } else {
					?>
                        <!-- Google Recaptcha -->
                        <script type="text/javascript">
                            var recaptchaVerifyCallback = function (response) {
                                jQuery("#submit_button_form_temporary_index_id").click()
                            };
                        </script>
                        <form>
                            <div
                                    class="g-recaptcha"
                                    data-sitekey="<?php echo $google_recaptcha_site_key; ?>"
                                    data-stoken="<?php echo $google_recaptcha_token; ?>"
                                    data-callback="recaptchaVerifyCallback"
                            >
                            </div>
                        </form>

                    <br/>

                    <input name="submit_button_form_temporary_index" id="submit_button_form_temporary_index_id"
                           style="display: none"
                           type="submit"
                           class="button-primary wdm-save"
                           value="Just to trigger submit on form"/>
						<?php
					}
					?>

                    <div class="wdm_row">
                        <h4 class="solr_error">
							<?php
							if ( ! empty( $response_error ) ) {
								echo $response_error;
							}
							?>
                        </h4>
                    </div>

                    <div class="wdm_note">
                        If you want to quickly test WPSOLR, without the burden of your own Solr/Elasticsearch
                        server.</br><br/>
                        Valid during 2 hours. After that, the index will be deleted automatically.<br/><br/>
                    </div>

                </div>
                <div class="clear"></div>
            </div>

    </form>
</div>

<div class="numberCircle">or</div>
<div style="clear: both; margin-bottom: 15px;"></div>
