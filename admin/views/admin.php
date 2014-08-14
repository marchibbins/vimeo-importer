<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Vimeo_Importer
 * @author    Marc Hibbins <marc@marchibbins.com>
 * @license   GPL-2.0+
 * @link      http://marchibbins.com
 */
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<form method="post" action="options-general.php?page=vimeo-importer">
		<p>Configure your Vimeo account settings</p>

		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="vimeo-importer-app-id">Application ID</label>
				</th>
				<td>
					<input type="text" id="vimeo-importer-app-id" name="app_id" value="<?php echo $options['app_id']?>">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="vimeo-importer-app-id">Application secret</label>
				</th>
				<td>
					<input type="text" id="vimeo-importer-app-secret" name="app_secret" value="<?php echo $options['app_secret']?>">
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<label for="vimeo-importer-access-token">Access token</label>
				</th>
				<td>
					<input type="text" id="vimeo-importer-access-token" name="access_token" value="<?php echo $options['access_token']?>">
				</td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" class="button button-primary" name="options_submit" value="Save Changes">
		</p>
	</form>
</div>
