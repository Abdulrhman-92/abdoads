<?php
/**
 * Membership checkout
 *
 * @author     RadiusTheme
 * @package    classified-listing/templates
 * @version    1.0.0
 */


use Rtcl\Helpers\Functions;
use RtclStore\Models\Membership;

$member = rtclStore()->factory->get_membership();
if ($member->has_membership()){
    echo 'you have membership';
}else{
    echo 'you dont have membership';

}
?>
<section
    class="elementor-section elementor-top-section elementor-element elementor-element-92ce9b2 elementor-section-boxed elementor-section-height-default elementor-section-height-default"
    data-id="92ce9b2" data-element_type="section"
    data-settings="{&quot;background_background&quot;:&quot;classic&quot;}">
    <div class="elementor-container elementor-column-gap-extended">
        <div class="elementor-row">
            <div class="elementor-column elementor-col-25 elementor-top-column elementor-element elementor-element-0f514ad"
                data-id="0f514ad" data-element_type="column">
                <div class="elementor-column-wrap elementor-element-populated">
                    <div class="elementor-widget-wrap">
                        <div class="elementor-element elementor-element-c239c65 elementor-widget elementor-widget-rt-pricing-box"
                            data-id="c239c65" data-element_type="widget" data-widget_type="rt-pricing-box.default">
                            <div class="elementor-widget-container row">
                                <div class="rt-el-pricing-box-2 col-md-3" >
                                    <div class="plan-contaner" >

                                        <h3 class="rtin-title" > Personal</h3>
                                                <div class="rtin-price">
                                                    <span class="rtin-currency">00.00$ </span>
                                                    <span class="rtin-duration">/ Per month</span>
                                                </div>
                                                <ul class="rtin-features ">
                                                <?php
                                                    global $wpdb;
         
                                                    $membership_free_ads = get_option('rtcl_membership_settings');
                                            

                                                        echo '<li>
                                                        <div class="rtcl-membership-promotions">
                                                            <div class="promotion-item label-item">
                                                                <div class="item-label"></div>
                                                                <div class="item-listings">Ads</div>
                                                                <div class="item-validate">Days</div>
                                                            </div>
                                                            <div class="promotion-item">
                                                                <div class="item-label">Regular</div>
                                                                <div class="item-listings">'.$membership_free_ads["number_of_free_ads"].'</div>
                                                                <div class="item-validate">1</div>
                                                            </div>
                                                            <div class="promotion-item">
                                                                <div class="item-label">Featured</div>
                                                                <div class="item-listings">0</div>
                                                                <div class="item-validate">0</div>
                                                            </div>
                                                            <div class="promotion-item">
                                                                <div class="item-label">Top</div>
                                                                <div class="item-listings">0</div>
                                                                <div class="item-validate">0</div>
                                                            </div>
                                                            <div class="promotion-item">
                                                                <div class="item-label">Bump Up</div>
                                                                <div class="item-listings">0</div>
                                                                <div class="item-validate">0</div>
                                                            </div>
                                                        </div>
                                                        </li>
                                                        <li> This plane is suported for all categories</li>'
                                                    
                                                ?>
                                                        
                                              
                                                </ul>
                                            </div>
                                            </div>
                           
                            <?php 
                                if (!empty($pricing_options)) {
                                   // Functions:: pre($pricing_options , 'pricing_options');
                                    foreach ($pricing_options as $option) {
                                        $price = get_post_meta($option->ID, 'price', true);
                                 
                                        echo '
                                            <div class="rt-el-pricing-box-2 col-md-3" >
                                            <div class="plan-contaner" >

                                                <h3 class="rtin-title" > 
                                                
                                        ';

                                        printf('<label><input  id= "'.$option->ID.'" type="radio" name="%s" value="%s" class="rtcl-checkout-pricing" required data-price="%s" onclick="doSomething(this.id);"/> %s</label>',
                                        'pricing_id', esc_attr($option->ID), esc_attr($price), esc_html($option->post_title));

                                        echo'
                                                </h3>
                                                <div class="rtin-price">
                                                    <span class="rtin-currency">
                                        ';

                                        echo Functions::get_formatted_amount($price, true); 

                                        echo'
                                                    $ </span>
                                                    <span class="rtin-duration">/ Per month</span>
                                                </div>
                                                <ul class="rtin-features ">
                                                    <li>
                                        ';

                                        do_action('rtcl_membership_features', $option->ID) ;
                                        $allcats=get_post_meta( $option->ID, 'membership_categories', true); 
                                       
                                        echo '</li>';
                                        if(is_array($allcats) &&!empty($allcats)){
                                            foreach ($allcats as $key=>$cat) {
                                                $cat_name =  get_the_category_by_ID($cat );
                                                echo' <li>'. $cat_name .'</li>';
    
                                            }
                                        }else{
                                            echo' <li>This plan is not support any category </li>';
                                        }
                                      

                                        echo'
                                                    
                                              
                                                </ul>
                                            </div>
                                            </div>
                                        ';

                                    }
                                }
                               
                            ?>
                               
                              
                            </div>
                        </div>
                    </div>
                </div>
            </div>
           
        </div>
    </div>
</section>

<!----------
<table id="rtcl-checkout-pricing-option"
    class="rtcl-responsive-table rtcl-pricing-options form-group table table-hover table-stripped table-bordered rtcl-membership-pricing-options">
    <tr>
        <th><?php esc_html_e("Membership", "classified-listing-store"); ?></th>
        <th><?php esc_html_e("Features", "classified-listing-store"); ?></th>
        <th><?php printf(__('Price [%s %s]', 'classified-listing-store'),
                Functions::get_currency(true),
                Functions::get_currency_symbol(null, true)); ?>
        </th>

    </tr>
    <?php if (!empty($pricing_options)) :
        foreach ($pricing_options as $option) :
            $price = get_post_meta($option->ID, 'price', true);
        ?>
    <tr>
        <td class="form-check rtcl-pricing-option"
            data-label="<?php esc_html_e("Membership:", "classified-listing-store"); ?>">
            <?php
                    printf('<label><input type="radio" name="%s" value="%s" class="rtcl-checkout-pricing" required data-price="%s" onclick="doSomething();"/> %s</label>',
                        'pricing_id', esc_attr($option->ID), esc_attr($price), esc_html($option->post_title));
                    ?>
        </td>

        <td class="rtcl-pricing-features" data-label="<?php esc_html_e("Features:", "classified-listing-store"); ?>">
            <?php do_action('rtcl_membership_features', $option->ID) ?>
        </td>

        <td class="rtcl-pricing-price text-right" data-label="<?php printf(__('Price [%s %s]:', 'classified-listing-store'),
                        Functions::get_currency(true),
                        Functions::get_currency_symbol(null, true)); ?>">
            <?php echo Functions::get_formatted_amount($price, true); ?>
        </td>

    </tr>

    <?php endforeach; ?>

    <?php endif; ?>
</table>
------------->
<br>
<div id="chooseCat" style="display: contents">
    <h4 class="pm-heading"><?php esc_html_e(" Select Category", "classified-listing-store"); ?></h4>

    <table
        class="rtcl-responsive-table rtcl-pricing-options form-group table table-hover table-stripped table-bordered rtcl-membership-pricing-options">

        <tr>

            <td class="rtcl-pricing-price text-left">
                <select id= "selectCat" name="select-cat" required>
                    <option value=""><?php echo esc_attr_e( 'Select ', 'abdoads' ); ?></option>
                    <?php 
                        
                        $taxonomies = get_terms( array( 
                            'taxonomy' => 'rtcl_category',
                            'parent'   => 0,
                            "hide_empty" => 0
                        ) );
                        $get_job_id = Functions:: get_jobs_category_id();
                        foreach( $taxonomies as $taxonomi ) {
                            if($taxonomi->term_id != $get_job_id){
                                echo '<option value="'.$taxonomi->term_id.'">';
                                echo  esc_attr_e( $taxonomi->name  , 'abdoads' ) ;
                                echo '</option>';

                            }
                        }
                    ?>
                </select>

            </td>
        </tr>
    </table>
</div>


<style>
    .plan-contaner{background-color: #f0f8ff;width:100%; margin-bottom:20px;height: 600px;}
    .plan-contaner:hover{ box-shadow: 0 0 3px rgba(33,33,33,.2);background-color:#775B6D;color:black;}
    .rt-el-pricing-box-2 .rtin-features li{padding: 10px 15px;}
    .rt-el-pricing-box-2 .rtin-title{padding: 10px 15px;font-size:30px;}
    .rt-el-pricing-box-2 .rtin-price{padding: 10px 15px;}
    .rt-el-pricing-box-2 .rtin-price .rtin-currency{font-size:30px;font-weight: 100;}
    .rt-el-pricing-box-2 .rtin-button{padding: 4px 15px;background-color: #E43D40;margin-bottom: 10px;border-radius: 5px;}
    .promotion-item {display: flex;justify-content: center;}
    .icon-postion{left: 26px;position: absolute;}
</style>

<script>
    function doSomething(element){
        var elem = document.getElementById(element);
        var price = elem.getAttribute('data-price');
        if (price == 0) {
            document.getElementById('chooseCat').style.display='none';
            document.getElementById('selectCat').removeAttribute("required");
            
        }else{
            document.getElementById('chooseCat').style.display='contents';
            document.getElementById('selectCat').attributes.required = "required";


        }

    }
</script>