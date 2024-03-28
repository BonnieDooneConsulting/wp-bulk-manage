<div class="grid_half">
    <form method="post" action="options.php">
		<?php settings_fields( $config_name . '_group' ); ?>
        <h3><?php _e( 'General Settings' ); ?></h3>
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">
					<?php _e( 'Logging Enabled:' ); ?>
                </th>
                <td>
                    <div class="radio">
                        <label>
                            <input type="radio" name="<?php echo $config_name; ?>[logging]"
                                   value="1" <?php if ( ! empty( $config['logging'] ) && $config['logging'] == 1 ) {
								echo 'checked';
							} ?>>
							<?php _e( 'On' ); ?>
                        </label>
                        <br>
                        <label>
                            <input type="radio" name="<?php echo $config_name; ?>[logging]"
                                   value="0" <?php if ( empty( $config['logging'] ) ) {
								echo 'checked';
							} ?>>
							<?php _e( 'Off' ); ?>
                        </label>
                        <p class="description">
                            Log events will be written to the default php error log
                        </p>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
		<?php submit_button( __( 'Save' ), 'primary', 'submit' ); ?>
        <hr/>
    </form>
