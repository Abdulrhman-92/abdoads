<?php

namespace Rtcl\Models;

use DateTime;
use Rtcl\Helpers\Functions;
use Rtcl\Resources\Options;

class RtclCFGField
{

    protected $_type;
    protected $_label;
    protected $_slug;
    protected $_placeholder;
    protected $_description;
    protected $_message;
    protected $_options = array();
    protected $_required;
    protected $_searchable;
    protected $_listable;
    protected $_default_value;
    protected $_validation;
    protected $_validation_message;
    protected $_rows;
    protected $_min;
    protected $_max;
    protected $_step_size;
    protected $_target;
    protected $_nofollow;
    protected $_field_id;
    protected $_meta_key;
    protected $_date_type;
    protected $_date_format;
    protected $_date_time_format;
    protected $_date_searchable_type;

    public function __construct($field_id = 0) {
        if ($post = get_post($field_id)) {
            $this->_field_id = $post->ID;
            $this->_type = get_post_meta($post->ID, '_type', true);
            $this->_label = get_post_meta($post->ID, '_label', true);
            $this->_slug = get_post_meta($post->ID, '_slug', true);
            $this->_description = get_post_meta($post->ID, '_description', true);
            $this->_message = get_post_meta($post->ID, '_message', true);
            $this->_placeholder = get_post_meta($post->ID, '_placeholder', true);
            $this->_options = get_post_meta($post->ID, '_options', true);
            $this->_validation = get_post_meta($post->ID, '_validation', true);
            $this->_validation_message = get_post_meta($post->ID, '_validation_message', true);
            $this->_required = get_post_meta($post->ID, '_required', true);
            $this->_searchable = get_post_meta($post->ID, '_searchable', true);
            $this->_listable = get_post_meta($post->ID, '_listable', true);
            $this->_default_value = get_post_meta($post->ID, '_default_value', true);
            $this->_rows = get_post_meta($post->ID, '_rows', true);
            $this->_min = get_post_meta($post->ID, '_min', true);
            $this->_max = get_post_meta($post->ID, '_max', true);
            $this->_step_size = get_post_meta($post->ID, '_step_size', true);
            $this->_target = get_post_meta($post->ID, '_target', true);
            $this->_nofollow = get_post_meta($post->ID, '_nofollow', true);
            $this->_date_format = get_post_meta($post->ID, '_date_format', true);
            $this->_date_time_format = get_post_meta($post->ID, '_date_time_format', true);
            $this->_date_type = get_post_meta($post->ID, '_date_type', true);
            $this->_date_searchable_type = get_post_meta($post->ID, '_date_searchable_type', true);
            $types = array_keys(Options::get_custom_field_list());
            $this->_meta_key = '_field_' . $post->ID;
            if (!$this->_type && !in_array($this->_type, $types)) {
                update_post_meta($post->id, '_type', 'text');
                $this->_type = "text";
            }
        } else {
            return false;
        }
    }

    public function get_meta($meta_key, $single = true) {
        if (!$meta_key) {
            return '';
        }

        return get_post_meta($this->getFieldId(), $meta_key, $single);
    }

    public function getAdminMetaValue($meta_key, $options = array()) {
        if (!Functions::meta_exist($this->getFieldId(), $meta_key)) {
            $value = $this->getAdminDefaultValue($options);
        } else {
            $value = $this->$meta_key;
        }

        return $value;
    }

    public function getAdminDefaultValue($options) {
        $default_value = null;
        if (isset($options['default'])) {
            if ($this->getType() == 'checkbox') {
                $default_value = !empty($options['default']) && is_array($options['default']) ? $options['default'] : array();
            } else {
                $default_value = !empty($options['default']) ? trim($options['default']) : null;
            }
        }

        return $default_value;
    }

    public function getValue($post_id) {
        $value = null;
        $type = $this->getType();
        if (!Functions::meta_exist($post_id, $this->getMetaKey()) && $type != 'date') {
            $value = $this->getDefaultValue();
        } else {
            if ($type == 'checkbox') {
                $value = get_post_meta($post_id, $this->getMetaKey());
            } elseif ($type == 'date') {
                $date_type = $this->getDateType();
                if (in_array($date_type, array('date_range', 'date_time_range'))) {
                    $value = [
                        'start' => get_post_meta($post_id, $this->getDateRangeMetaKey('start'), true),
                        'end'   => get_post_meta($post_id, $this->getDateRangeMetaKey('end'), true)
                    ];
                } else {
                    $value = get_post_meta($post_id, $this->getMetaKey(), true);
                }
            } else {
                $value = get_post_meta($post_id, $this->getMetaKey(), true);
            }
        }

        return $value;
    }

    /**
     * @param int $post_id  Listing id
     *
     * @return array|mixed|string|null
     */
    public function getFormattedCustomFieldValue($post_id) {

        $value = $this->getValue($post_id);
        if ('url' == $this->getType() && filter_var($value, FILTER_VALIDATE_URL)) {
            $value = esc_url($value);
//			$nofollow = ! empty( $this->getNofollow() ) ? ' rel="nofollow"' : '';
//			$value    = sprintf( '<a href="%1$s" target="%2$s"%3$s>%1$s</a>', $value,
//				$this->getTarget(),
//				$nofollow );
        } else if (in_array($this->getType(), array('select', 'radio'))) {
            $options = $this->getOptions();
            if (!empty($options['choices']) && !empty($options['choices'][$value])) {
                $value = $options['choices'][$value];
            }
        } else if ('checkbox' == $this->getType() && is_array($value)) {
            $options = $this->getOptions();
            $items = array();
            if (!empty($options['choices'])) {
                foreach ($value as $item) {
                    if (!empty($options['choices'][$item])) {
                        $items[] = $options['choices'][$item];
                    }
                }
            }
            if (!empty($items)) {
                $value = implode(", ", $items);
            }
        } else if ('date' == $this->getType()) {
            $date_format = $this->getDateFullFormat();
            $date_type = $this->getDateType();
            if (($date_type == 'date_range' || $date_type == 'date_time_range') && is_array($value)) {
                $start = isset($value['start']) && !empty($value['start']) ? date($date_format, strtotime($value['start'])) : null;
                $end = isset($value['end']) && !empty($value['start']) ? date($date_format, strtotime($value['end'])) : null;
                $value = $end ? $start . " - " . $end : $start;
            } else {
                $value = !empty($value) ? date($date_format, strtotime($value)) : '';
            }

        } else if ('text' == $this->getType()) {
            $value = esc_html($value);
        } else if ('textarea' == $this->getType()) {
            $value = esc_html($value);
        }

        return apply_filters('rtcl_formatted_custom_field_value', $value, $this);
    }

    public function get_field_data() {
        $html = null;
        // Set right ID if existing field
        $clasess = 'postbox rtcl-cf-postbox';
        $id = $this->_type . '-' . $this->_field_id;
        if ($this->_slug) {
            $clasess = 'closed ' . $clasess;
        }

        $icon = Options::get_custom_field_list()[$this->_type]['symbol'];
        $icon = "<i class='rtcl-icon rtcl-icon-{$icon}'></i>";
        $title = !empty($this->_label) ? $this->_label : __('Untitled', 'classified-listing');
        $title = sprintf(
            '<span class="rtcl-legend-update">%s</span> <span class="description">(%s)</span>',
            stripslashes($title),
            Options::get_custom_field_list()[$this->_type]['name']
        );

        $box_id = sprintf('rtcl-custom-field-%s', $id);
        $html = sprintf(
            '<div id="%s" class="%s" data-id="%s"><div class="postbox-header"><h2 class="hndle ui-sortable-handle">%s%s</h2><div class="handle-actions hide-if-no-js"><button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">%s</span><span class="toggle-indicator" aria-hidden="false"></span></button></div></div><div class="inside">%s</div></div>',
            esc_attr($box_id),
            esc_attr($clasess),
            $this->_field_id,
            $icon,
            $title,
            esc_attr__('Toggle for Meta field', 'classified-listing'),
            $this->render()
        );

        return $html;
    }

    private function render() {
        $html = null;
        $field_id =$this->_field_id;
        $post_id = get_the_ID();
        $url = get_site_url( null, "/wp-admin/admin-ajax.php", null );
        $icons = array (
            'fab fa-500px '=>'  500px',
            'fab fa-accessible-icon '=>'  accessible-icon',
            'fab fa-accusoft '=>'  accusoft',
            'fab fa-acquisitions-incorporated '=>'  acquisitions-incorporated',
            'fab fa-adn '=>'  adn',
            'fab fa-adversal '=>'  adversal',
            'fab fa-affiliatetheme '=>'  affiliatetheme',
            'fab fa-airbnb '=>'  airbnb',
            'fab fa-algolia '=>'  algolia',
            'fab fa-alipay '=>'  alipay',
            'fab fa-amazon '=>'  amazon',
            'fab fa-amazon-pay '=>'  amazon-pay',
            'fab fa-wikipedia-w '=>'  wikipedia-w',
            'fab fa-windows '=>'  windows',
            'fab fa-wix '=>'  wix',
            'fab fa-wizards-of-the-coast '=>'  wizards-of-the-coast',
            'fab fa-wodu '=>'  wodu',
            'fab fa-wolf-pack-battalion '=>'  wolf-pack-battalion',
            'fab fa-wordpress '=>'  wordpress',
            'fab fa-wordpress-simple '=>'  wordpress-simple',
            'fab fa-wpbeginner '=>'  wpbeginner',
            'fab fa-wpexplorer '=>'  wpexplorer',
            'fab fa-wpforms '=>'  wpforms',
            'fab fa-wpressr '=>'  wpressr',
            'fab fa-xbox '=>'  xbox',
            'fab fa-xing '=>'  xing',
            'fab fa-xing-square '=>'  xing-square',
            'fab fa-y-combinator '=>'  y-combinator',
            'fab fa-yahoo '=>'  yahoo',
            'fab fa-yammer '=>'  yammer',
            'fab fa-yandex '=>'  yandex',
            'fab fa-yandex-international '=>'  yandex-international',
            'fab fa-yarn '=>'  yarn',
            'fab fa-yelp '=>'  yelp',
            'fab fa-yoast '=>'  yoast',
            'fab fa-youtube '=>'  youtube',
            'fab fa-youtube-square '=>'  youtube-square', 
            'fab fa-amilia '=>'  amilia',
            'fab fa-android '=>'  android',
            'fab fa-angellist '=>'  angellist',
            'fab fa-angrycreative '=>'  angrycreative',
            'fab fa-angular '=>'  angular',
            'fab fa-app-store '=>'  app-store',
            'fab fa-app-store-ios '=>'  app-store-ios',
            'fab fa-apper '=>'  apper',
            'fab fa-apple '=>'  apple',
            'fab fa-apple-pay '=>'  apple-pay', 
            'fab fa-artstation '=>'  artstation',
            'fab fa-asymmetrik '=>'  asymmetrik',
            'fab fa-atlassian '=>'  atlassian',
            'fab fa-audible '=>'  audible',
            'fab fa-autoprefixer '=>'  autoprefixer',
            'fab fa-avianex '=>'  avianex',
            'fab fa-aviato '=>'  aviato',
            'fab fa-aws '=>'  aws',
            'fab fa-bandcamp '=>'  bandcamp',
            'fab fa-battle-net '=>'  battle-net',
            'fab fa-behance '=>'  behance',
            'fab fa-behance-square '=>'  behance-square',
            'fab fa-bimobject '=>'  bimobject',
            'fab fa-bitbucket '=>'  bitbucket',
            'fab fa-bitcoin '=>'  bitcoin',
            'fab fa-bity '=>'  bity',
            'fab fa-black-tie '=>'  black-tie',
            'fab fa-blackberry '=>'  blackberry',
            'fab fa-blogger '=>'  blogger',
            'fab fa-blogger-b '=>'  blogger-b',
            'fab fa-bluetooth '=>'  bluetooth',
            'fab fa-bluetooth-b '=>'  bluetooth-b',
            'fab fa-bootstrap '=>'  bootstrap',   
            'fab fa-btc '=>'  btc',
            'fab fa-buffer '=>'  buffer',
            'fab fa-buromobelexperte '=>'  buromobelexperte',
            'fab fa-buy-n-large '=>'  buy-n-large',
            'fab fa-buysellads '=>'  buysellads',
            'fab fa-canadian-maple-leaf '=>'  canadian-maple-leaf',
            'fab fa-cc-amazon-pay '=>'  cc-amazon-pay',
            'fab fa-cc-amex '=>'  cc-amex',
            'fab fa-cc-apple-pay '=>'  cc-apple-pay',
            'fab fa-cc-diners-club '=>'  cc-diners-club',
            'fab fa-cc-discover '=>'  cc-discover',
            'fab fa-cc-jcb '=>'  cc-jcb',
            'fab fa-cc-mastercard '=>'  cc-mastercard',
            'fab fa-cc-paypal '=>'  cc-paypal',
            'fab fa-cc-stripe '=>'  cc-stripe',
            'fab fa-cc-visa '=>'  cc-visa',
            'fab fa-centercode '=>'  centercode',
            'fab fa-centos '=>'  centos',
            'fab fa-chrome '=>'  chrome',
            'fab fa-chromecast '=>'  chromecast',
            'fab fa-cloudflare '=>'  cloudflare',
            'fab fa-cloudscale '=>'  cloudscale',
            'fab fa-cloudsmith '=>'  cloudsmith',
            'fab fa-cloudversify '=>'  cloudversify',
            'fab fa-codepen '=>'  codepen',
            'fab fa-codiepie '=>'  codiepie',
            'fab fa-confluence '=>'  confluence',
            'fab fa-connectdevelop '=>'  connectdevelop',
            'fab fa-contao '=>'  contao',
            'fab fa-cotton-bureau '=>'  cotton-bureau',
            'fab fa-cpanel '=>'  cpanel',
            'fab fa-creative-commons '=>'  creative-commons',
            'fab fa-creative-commons-by '=>'  creative-commons-by',
            'fab fa-creative-commons-nc '=>'  creative-commons-nc',
            'fab fa-creative-commons-nc-eu '=>'  creative-commons-nc-eu',
            'fab fa-creative-commons-nc-jp '=>'  creative-commons-nc-jp',
            'fab fa-creative-commons-nd '=>'  creative-commons-nd',
            'fab fa-creative-commons-pd '=>'  creative-commons-pd',
            'fab fa-creative-commons-pd-alt '=>'  creative-commons-pd-alt',
            'fab fa-creative-commons-remix '=>'  creative-commons-remix',
            'fab fa-creative-commons-sa '=>'  creative-commons-sa',
            'fab fa-creative-commons-sampling '=>'  creative-commons-sampling',
            'fab fa-creative-commons-sampling-plus '=>'  creative-commons-sampling-plus',
            'fab fa-creative-commons-share '=>'  creative-commons-share',
            'fab fa-creative-commons-zero '=>'  creative-commons-zero',
            'fab fa-critical-role '=>'  critical-role',
            'fab fa-css3 '=>'  css3',
            'fab fa-css3-alt '=>'  css3-alt',
            'fab fa-cuttlefish '=>'  cuttlefish',
            'fab fa-d-and-d '=>'  d-and-d',
            'fab fa-d-and-d-beyond '=>'  d-and-d-beyond',
            'fab fa-dailymotion '=>'  dailymotion',
            'fab fa-dashcube '=>'  dashcube',
            'fab fa-deezer '=>'  deezer',
            'fab fa-delicious '=>'  delicious',
            'fab fa-deploydog '=>'  deploydog',
            'fab fa-deskpro '=>'  deskpro',
            'fab fa-dev '=>'  dev',
            'fab fa-deviantart '=>'  deviantart',
            'fab fa-dhl '=>'  dhl',
            'fab fa-diaspora '=>'  diaspora',
            'fab fa-digg '=>'  digg',
            'fab fa-digital-ocean '=>'  digital-ocean',
            'fab fa-discord '=>'  discord',
            'fab fa-discourse '=>'  discourse',
            'fab fa-dochub '=>'  dochub',
            'fab fa-docker '=>'  docker',
            'fab fa-draft2digital '=>'  draft2digital',
            'fab fa-dribbble '=>'  dribbble',
            'fab fa-dribbble-square '=>'  dribbble-square',
            'fab fa-dropbox '=>'  dropbox',
            'fab fa-drupal '=>'  drupal',
            'fab fa-dyalog '=>'  dyalog',
            'fab fa-earlybirds '=>'  earlybirds',
            'fab fa-ebay '=>'  ebay',
            'fab fa-edge '=>'  edge',
            'fab fa-edge-legacy '=>'  edge-legacy',
            'fab fa-elementor '=>'  elementor',
            'fab fa-ello '=>'  ello',
            'fab fa-ember '=>'  ember',
            'fab fa-empire '=>'  empire',
            'fab fa-envira '=>'  envira',
            'fab fa-erlang '=>'  erlang',
            'fab fa-ethereum '=>'  ethereum',
            'fab fa-etsy '=>'  etsy',
            'fab fa-evernote '=>'  evernote',
            'fab fa-expeditedssl '=>'  expeditedssl',
            'fab fa-facebook '=>'  facebook',
            'fab fa-facebook-f '=>'  facebook-f',
            'fab fa-facebook-messenger '=>'  facebook-messenger',
            'fab fa-facebook-square '=>'  facebook-square',
            'fab fa-fantasy-flight-games '=>'  fantasy-flight-games',
            'fab fa-fedex '=>'  fedex',
            'fab fa-fedora '=>'  fedora',
            'fab fa-figma '=>'  figma',
            'fab fa-firefox '=>'  firefox]',
            'fab fa-firefox-browser '=>'  firefox-browser',
            'fab fa-first-order '=>'  first-order',
            'fab fa-first-order-alt '=>'  first-order-alt',
            'fab fa-firstdraft '=>'  firstdraft',
            'fab fa-flickr '=>'  flickr',
            'fab fa-flipboard '=>'  flipboard',
            'fab fa-fly '=>'  fly',
            'fab fa-font-awesome '=>'  font-awesome',
            'fab fa-font-awesome-alt '=>'  font-awesome-alt',
            'fab fa-font-awesome-flag '=>'  font-awesome-flag',
            'fab fa-font-awesome-logo-full '=>'  font-awesome-logo-full',
            'fab fa-fonticons '=>'  fonticons',
            'fab fa-fonticons-fi '=>'  fonticons-fi',
            'fab fa-fort-awesome '=>'  fort-awesome',
            'fab fa-fort-awesome-alt '=>'  fort-awesome-alt',
            'fab fa-forumbee '=>'  forumbee',
            'fab fa-foursquare '=>'  foursquare',
            'fab fa-free-code-camp '=>'  free-code-camp',
            'fab fa-freebsd '=>'  freebsd',
            'fab fa-fulcrum '=>'  fulcrum',
            'fab fa-galactic-republic '=>'  galactic-republic',
            'fab fa-galactic-senate '=>'  galactic-senate',
            'fab fa-get-pocket '=>'  get-pocket',
            'fab fa-gg '=>'  gg',
            'fab fa-gg-circle '=>'  gg-circle',
            'fab fa-git '=>'  git',
            'fab fa-git-alt '=>'  git-alt',
            'fab fa-git-square '=>'  git-square',
            'fab fa-github '=>'  github',
            'fab fa-github-alt '=>'  github-alt',
            'fab fa-github-square '=>'  github-square',
            'fab fa-gitkraken '=>'  gitkraken',
            'fab fa-gitlab '=>'  gitlab',
            'fab fa-gitter '=>'  gitter',
            'fab fa-glide '=>'  glide',
            'fab fa-glide-g '=>'  glide-g',
            'fab fa-gofore '=>'  gofore',
            'fab fa-goodreads '=>'  goodreads',
            'fab fa-goodreads-g '=>'  goodreads-g',
            'fab fa-google '=>'  google',
            'fab fa-google-drive '=>'  google-drive',
            'fab fa-google-pay '=>'  google-pay',
            'fab fa-google-play '=>'  google-play',
            'fab fa-google-plus '=>'  google-plus',
            'fab fa-google-plus-g '=>'  google-plus-g',
            'fab fa-google-plus-square '=>'  google-plus-square',
            'fab fa-google-wallet '=>'  google-wallet',
            'fab fa-gratipay '=>'  gratipay',
            'fab fa-grav '=>'  grav',
            'fab fa-gripfire '=>'  gripfire',
            'fab fa-grunt '=>'  grunt',
            'fab fa-guilded '=>'  guilded',
            'fab fa-gulp '=>'  gulp',
            'fab fa-hacker-news '=>'  hacker-news',
            'fab fa-hacker-news-square '=>'  hacker-news-square',   
            'fab fa-hackerrank '=>'  hackerrank',
            'fab fa-hips '=>'  hips',
            'fab fa-hire-a-helper '=>'  hire-a-helper',
            'fab fa-hive '=>'  hive',
            'fab fa-hooli '=>'  hooli',
            'fab fa-hornbill '=>'  hornbill',
            'fab fa-hotjar '=>'  hotjar',
            'fab fa-houzz '=>'  houzz',
            'fab fa-html5 '=>'  html5',
            'fab fa-hubspot '=>'  hubspot',
            'fab fa-ideal '=>'  ideal',
            'fab fa-imdb '=>'  imdb',
            'fab fa-innosoft '=>'  innosoft',
            'fab fa-instagram '=>'  instagram',
            'fab fa-instagram-square '=>'  instagram-square',
            'fab fa-instalod '=>'  instalod',
            'fab fa-intercom '=>'  intercom',
            'fab fa-internet-explorer '=>'  internet-explorer',
            'fab fa-invision '=>'  invision',
            'fab fa-ioxhost '=>'  ioxhost',
            'fab fa-itch-io '=>'  itch-io',
            'fab fa-itunes '=>'  itunes',
            'fab fa-itunes-note '=>'  itunes-note',
            'fab fa-java '=>'  java',
            'fab fa-jedi-order '=>'  jedi-order',
            'fab fa-jenkins '=>'  jenkins',
            'fab fa-jira '=>'  jira',
            'fab fa-joget '=>'  joget',
            'fab fa-joomla '=>'  joomla',
            'fab fa-js '=>'  js',
            'fab fa-js-square '=>'  js-square',
            'fab fa-jsfiddle '=>'  jsfiddle',
            'fab fa-kaggle '=>'  kaggle',
            'fab fa-keybase '=>'  keybase',
            'fab fa-keycdn '=>'  keycdn',
            'fab fa-kickstarter '=>'  kickstarter',
            'fab fa-kickstarter-k '=>'  kickstarter-k',
            'fab fa-korvue '=>'  korvue',
            'fab fa-laravel '=>'  laravel',
            'fab fa-lastfm '=>'  lastfm',
            'fab fa-lastfm-square '=>'  lastfm-square',
            'fab fa-leanpub '=>'  leanpub',
            'fab fa-less '=>'  less',
            'fab fa-line '=>'  line',
            'fab fa-linkedin '=>'  linkedin',
            'fab fa-linkedin-in '=>'  linkedin-in',
            'fab fa-linode '=>'  linode',
            'fab fa-linux '=>'  linux',
            'fab fa-lyft '=>'  lyft',
            'fab fa-magento '=>'  magento',
            'fab fa-mailchimp '=>'  mailchimp',
            'fab fa-mandalorian '=>'  mandalorian',
            'fab fa-markdown '=>'  markdown',
            'fab fa-mastodon '=>'  mastodon',
            'fab fa-maxcdn '=>'  maxcdn',
            'fab fa-mdb '=>'  mdb',
            'fab fa-medapps '=>'  medapps',
            'fab fa-medium '=>'  medium',
            'fab fa-medium-m '=>'  medium-m',
            'fab fa-medrt '=>'  medrt',
            'fab fa-meetup '=>'  meetup',
            'fab fa-megaport '=>'  megaport',
            'fab fa-mendeley '=>'  mendeley',
            'fab fa-microblog '=>'  microblog',
            'fab fa-microsoft '=>'  microsoft',
            'fab fa-mix '=>'  mix',
            'fab fa-mixcloud '=>'  mixcloud',
            'fab fa-mixer '=>'  mixer',
            'fab fa-mizuni '=>'  mizuni',
            'fab fa-modx '=>'  modx',
            'fab fa-monero '=>'  monero',
            'fab fa-napster '=>'  napster',
            'fab fa-neos '=>'  neos',
            'fab fa-nimblr '=>'  nimblr',
            'fab fa-node '=>'  node',
            'fab fa-node-js '=>'  node-js',
            'fab fa-npm '=>'  npm',
            'fab fa-ns8 '=>'  ns8',
            'fab fa-nutritionix '=>'  nutritionix',
            'fab fa-octopus-deploy '=>'  octopus-deploy',
            'fab fa-odnoklassniki '=>'  odnoklassniki',
            'fab fa-odnoklassniki-square '=>'  odnoklassniki-square',
            'fab fa-old-republic '=>'  old-republic',
            'fab fa-opencart '=>'  opencart',
            'fab fa-openid '=>'  openid',
            'fab fa-opera '=>'  opera',
            'fab fa-optin-monster '=>'  optin-monster',
            'fab fa-orcid '=>'  orcid',
            'fab fa-osi '=>'  osi',
            'fab fa-page4 '=>'  page4',
            'fab fa-pagelines '=>'  pagelines',
            'fab fa-palfed '=>'  palfed',
            'fab fa-patreon '=>'  patreon',
            'fab fa-paypal '=>'  paypal',
            'fab fa-penny-arcade '=>'  penny-arcade',
            'fab fa-perbyte '=>'  perbyte',
            'fab fa-periscope '=>'  periscope',
            'fab fa-phabricator '=>'  phabricator',
            'fab fa-phoenix-framework '=>'  phoenix-framework',
            'fab fa-phoenix-squadron '=>'  phoenix-squadron',
            'fab fa-php '=>'  php',
            'fab fa-pied-piper '=>'  pied-piper',
            'fab fa-pied-piper-alt '=>'  pied-piper-alt',
            'fab fa-pied-piper-hat '=>'  pied-piper-hat',
            'fab fa-pied-piper-pp '=>'  pied-piper-pp',
            'fab fa-pied-piper-square '=>'  pied-piper-square',
            'fab fa-pinterest '=>'  pinterest',
            'fab fa-pinterest-p '=>'  pinterest-p',
            'fab fa-pinterest-square '=>'  pinterest-square',
            'fab fa-playstation '=>'  playstation',
            'fab fa-product-hunt '=>'  product-hunt',
            'fab fa-pushed '=>'  pushed',
            'fab fa-python '=>'  python',
            'fab fa-qq '=>'  qq',
            'fab fa-quinscape '=>'  quinscape',
            'fab fa-quora '=>'  quora',
            'fab fa-r-project '=>'  r-project',
            'fab fa-raspberry-pi '=>'  raspberry-pi',
            'fab fa-ravelry '=>'  ravelry',
            'fab fa-react '=>'  react',
            'fab fa-reacteurope '=>'  reacteurope',
            'fab fa-readme '=>'  readme',
            'fab fa-rebel '=>'  rebel',
            'fab fa-red-river '=>'  red-river',
            'fab fa-reddit '=>'  reddit',
            'fab fa-reddit-alien '=>'  reddit-alien',
            'fab fa-reddit-square '=>'  reddit-square',
            'fab fa-redhat '=>'  redhat',
            'fab fa-renren '=>'  renren',
            'fab fa-replyd '=>'  replyd',
            'fab fa-researchgate '=>'  researchgate',
            'fab fa-resolving '=>'  resolving',
            'fab fa-rev '=>'  rev',
            'fab fa-rocketchat '=>'  rocketchat',
            'fab fa-rockrms '=>'  rockrms',
            'fab fa-rust '=>'  rust',
            'fab fa-safari '=>'  safari',
            'fab fa-salesforce '=>'  salesforce',
            'fab fa-sass '=>'  sass',
            'fab fa-schlix '=>'  schlix',
            'fab fa-scribd '=>'  scribd',
            'fab fa-searchengin '=>'  searchengin',
            'fab fa-sellcast '=>'  sellcast',
            'fab fa-sellsy '=>'  sellsy',
            'fab fa-servicestack '=>'  servicestack',
            'fab fa-shirtsinbulk '=>'  shirtsinbulk',
            'fab fa-shopify '=>'  shopify',
            'fab fa-shopware '=>'  shopware',
            'fab fa-simplybuilt '=>'  simplybuilt',
            'fab fa-sistrix '=>'  sistrix',
            'fab fa-sith '=>'  sith',
            'fab fa-sketch '=>'  sketch',
            'fab fa-skyatlas '=>'  skyatlas',
            'fab fa-skype '=>'  skype',
            'fab fa-slack '=>'  slack',
            'fab fa-slack-hash '=>'  slack-hash',
            'fab fa-slideshare '=>'  slideshare',
            'fab fa-snapchat '=>'  snapchat',
            'fab fa-snapchat-ghost '=>'  snapchat-ghost',
            'fab fa-snapchat-square '=>'  snapchat-square',
            'fab fa-soundcloud '=>'  soundcloud',
            'fab fa-sourcetree '=>'  sourcetree',
            'fab fa-speakap '=>'  speakap',
            'fab fa-speaker-deck '=>'  speaker-deck',
            'fab fa-spotify '=>'  spotify',
            'fab fa-squarespace '=>'  squarespace',
            'fab fa-stack-exchange '=>'  stack-exchange',
            'fab fa-stack-overflow '=>'  stack-overflow',
            'fab fa-stackpath '=>'  stackpath',
            'fab fa-staylinked '=>'  staylinked',
            'fab fa-steam '=>'  steam',
            'fab fa-steam-square '=>'  steam-square',
            'fab fa-steam-symbol '=>'  steam-symbol',
            'fab fa-sticker-mule '=>'  sticker-mule',
            'fab fa-strava '=>'  strava',
            'fab fa-stripe '=>'  stripe',
            'fab fa-stripe-s '=>'  stripe-s',
            'fab fa-studiovinari '=>'  studiovinari',
            'fab fa-stumbleupon '=>'  stumbleupon',
            'fab fa-stumbleupon-circle '=>'  stumbleupon-circle',
            'fab fa-superpowers '=>'  superpowers',
            'fab fa-supple '=>'  supple',
            'fab fa-suse '=>'  suse',
            'fab fa-swift '=>'  swift',
            'fab fa-symfony '=>'  symfony',
            'fab fa-teamspeak '=>'  teamspeak',
            'fab fa-telegram '=>'  telegram',
            'fab fa-telegram-plane '=>'  telegram-plane',
            'fab fa-tencent-weibo '=>'  tencent-weibo',
            'fab fa-the-red-yeti '=>'  the-red-yeti',
            'fab fa-themeco '=>'  themeco',
            'fab fa-themeisle '=>'  themeisle',
            'fab fa-think-peaks '=>'  think-peaks',
            'fab fa-tiktok '=>'  tiktok',
            'fab fa-trade-federation '=>'  trade-federation',
            'fab fa-trello '=>'  trello',
            'fab fa-tripadvisor '=>'  tripadvisor',
            'fab fa-tumblr '=>'  tumblr',
            'fab fa-tumblr-square '=>'  tumblr-square',
            'fab fa-twitch '=>'  twitch',
            'fab fa-twitter '=>'  twitter',
            'fab fa-twitter-square '=>'  twitter-square',
            'fab fa-typo3 '=>'  typo3',
            'fab fa-uber '=>'  uber',
            'fab fa-ubuntu '=>'  ubuntu',
            'fab fa-uikit '=>'  uikit',
            'fab fa-umbraco '=>'  umbraco',
            'fab fa-uncharted '=>'  uncharted',
            'fab fa-uniregistry '=>'  uniregistry',
            'fab fa-unity '=>'  unity',
            'fab fa-unsplash '=>'  unsplash',
            'fab fa-untappd '=>'  untappd',
            'fab fa-ups '=>'  ups',
            'fab fa-usb '=>'  usb',
            'fab fa-usps '=>'  usps',
            'fab fa-ussunnah '=>'  ussunnah',
            'fab fa-vaadin '=>'  vaadin',
            'fab fa-viacoin '=>'  viacoin',
            'fab fa-viadeo '=>'  viadeo',
            'fab fa-viadeo-square '=>'  viadeo-square',
            'fab fa-viber '=>'  viber',
            'fab fa-vimeo '=>'  vimeo',
            'fab fa-vimeo-square '=>'  vimeo-square',
            'fab fa-vimeo-v '=>'  vimeo-v',
            'fab fa-vine '=>'  vine',
            'fab fa-vk '=>'  vk',
            'fab fa-vnv '=>'  vnv',
            'fab fa-vuejs '=>'  vuejs',
            'fab fa-watchman-monitoring '=>'  watchman-monitoring',
            'fab fa-waze '=>'  waze',
            'fab fa-weebly '=>'  weebly',
            'fab fa-weibo '=>'  weibo',
            'fab fa-weixin '=>'  weixin',
            'fab fa-whatsapp '=>'  whatsapp',
            'fab fa-whatsapp-square '=>'  whatsapp-square',
            'fab fa-whmcs '=>'  whmcs',
            'fab fa-zhihu '=>'  zhihu'
        );
       // Functions:: pre($icons);

        $options = Options::get_custom_field_list()[$this->_type]['options'];
        if (!empty($options)) {
            foreach ($options as $optName => $option) {
                $id = $this->_type . '-' . rand();
                $html .= "<div class='rtcl-cfg-field-group'>";
                $html .= "<div class='rtcl-cfg-field-label'><label class='rtcl-cfg-label' for='{$id}'>{$option['label']}</label></div>";
                $html .= "<div class='rtcl-cfg-field'>" . $this->createField($optName, $id, $option) . "</div>";
                $html .= "</div>";

            }
            $html .= "

                <div class='rtcl-cfg-field-group '>
                    <div class='rtcl-cfg-field-label  '>
                        <label class='rtcl-cfg-label ' for='icon'>Select Icon</label>
                    
                    </div>
                    <select name='icons' id='icons_$field_id' class='icon_lable_abdoadz icon-abdoadz' onchange='append_child(this)'>
            ";
            $post_meta =  get_post_meta($post_id, 'icons_'.$field_id);
            $post_meta_key_exsist   =   !empty($post_meta) ? 1 : 0 ;
         
            if($post_meta_key_exsist == 1){
                foreach($icons as $key=>$icon){
                    if ($post_meta[0] == $key ) {
                        $html .='
                            <option value="'.$key.'" >
                                <i class="'.$key.'"/>'.$icon.'
                            </option>
        
                        ';
                    }
     
                }
            
            }else{
                $html .="
                    <option value=''>select icon</option>

                ";
            }
            foreach($icons as $key=>$icon){
                $html .='
                    <option value="'.$key.'" >
                        <i class="'.$key.'"/>'.$icon.'
                    </option>

                ';
            }
            $html .="
       
                    </select>

                </div>
            
            ";
            $html .= '
                <script>
                    function append_child(element){
                        var id                  =   element.id; 
                        var value               =   document.getElementById(id).value;
                        jQuery.ajax({
                            url: "'.$url.'",
                            type: "POST",

                            data: {
                                action: "icons_abdoadz",
                                value: value,
                                post_id : '.$post_id.',
                                id_feild : id ,
            
                            },
                        })
                    }
                </script>
            ';

           // add_post_meta( $post_id, 'icons', mixed $meta_value, bool $unique = false )

        }

        $html .= '<span href="#" class="js-rtcl-field-remove rtcl-field-remove" data-message-confirm="' . __("Are you sure?",
                "classified-listing") . '"><span class="dashicons dashicons-trash"></span> Remove field</a>';

        return $html;
    }

    private function createField($optName, $id, $option = array()) {
        $html = null;
        $type = $option['type'];
        $placeholder = !empty($option['placeholder']) ? " placeholder='{$option['placeholder']}'" : null;
        $class = !empty($option['class']) ? $option['class'] : null;
        
        switch ($type) {
            case 'true_false':
                $html .= "<input id='{$id}' value='1' class='widefat {$class}' type='checkbox' name='rtcl[fields][{$this->_field_id}][{$optName}]'>";
                break;
            case 'checkbox':
            case 'select':
                $html .= "<div class='rtcl-select-options-wrap' data-type='{$type}'>";
                $html .= "<table class='striped rtcl-select-options-table rtcl-fields-field-value-options'>
									<thead>
										<tr>
											<th> </th>
											<th>" . __('Display text', 'classified-listing') . "</th>
											<th>" . __('Value', 'classified-listing') . "</th>
											<th>" . __('Default', 'classified-listing') . "</th>
											<th> </th>
										</tr>
									</thead>";
                $html .= "<tbody class='rtcl-fields-select-sortable'>";
                $default_name = "rtcl[fields][{$this->_field_id}][{$optName}][default]";
                $default_type = "radio";
                if ($type == 'checkbox') {
                    $default_name = "rtcl[fields][{$this->_field_id}][{$optName}][default][]";
                    $default_type = 'checkbox';
                }
                if (!empty($this->_options['choices'])) {
                    foreach ($this->_options['choices'] as $optId => $option) {
                        $id = uniqid();
                        $checked = !empty($this->_options['default']) && $this->_options['default'] == $optId ? " checked='checked'" : null;
                        if ($type == 'checkbox') {
                            $defaultValues = $this->_options['default'];
                            $checked = !empty($defaultValues) && is_array($defaultValues) && in_array($optId,
                                $defaultValues) == $optId ? " checked='checked'" : null;
                        }
                        $html .= "<tr>
												<td class='num'><span class='js-types-sort-button hndle dashicons dashicons-menu'></span></td>
												<td><input type='text' name='rtcl[fields][{$this->_field_id}][{$optName}][choices][{$id}][title]' value='{$option}' ></td>
												<td><input type='text' name='rtcl[fields][{$this->_field_id}][{$optName}][choices][{$id}][value]' value='{$optId}' ></td>
												<td class='num'><input type='{$default_type}' name='{$default_name}' {$checked} value='{$id}' ></td>
												<td class='num'><span class='rtcl-delete-option dashicons dashicons-trash'></span></td>
											</tr>";
                    }
                } else {
                    $id = uniqid();
                    $html .= "<tr>
											<td class='num'><span class='js-types-sort-button hndle dashicons dashicons-menu'></span></td>
											<td><input type='text' name='rtcl[fields][{$this->_field_id}][{$optName}][choices][{$id}][title]' value='Option title 1' ></td>
											<td><input type='text' name='rtcl[fields][{$this->_field_id}][{$optName}][choices][{$id}][value]' value='option-title-1' ></td>
											<td class='num'><input type='{$default_type}' name='{$default_name}' value='{$id}' ></td>
											<td class='num'><span class='rtcl-delete-option dashicons dashicons-trash'></span></td>
										</tr>";
                }
                $html .= "</tbody>";
                if ($type == 'select') {
                    $ndId = 'select-radio-' . time();
                    $ndChecked = empty($this->_options['default']) ? " checked='checked'" : null;
                    $html .= "<tfoot><td> </td><td> </td><td><label for='{$ndId}'>" . __("No Default",
                            "classified-listing") . "</label></td><td><input id='$ndId' type='radio' name='{$default_name}' $ndChecked value='' ></td><td> </td><tfoot>";
                }
                $html .= "</table>";
                $html .= "<a class='button rtcl-add-new-option' data-name='rtcl[fields][{$this->_field_id}][{$optName}]'>" . __("Add Option",
                        "classified-listing") . "</a>";
                $html .= "</div>";


                break;
            case 'number':
                $html .= "<input $placeholder id='{$id}' value='{$this->$optName}' class='widefat {$class}' type='number' step='any' name='rtcl[fields][{$this->_field_id}][{$optName}]'>";
                break;
            case 'textarea':
                $html .= "<textarea rows='5' $placeholder name='rtcl[fields][{$this->_field_id}][{$optName}]' class='widefat {$class}' id='{$id}'>{$this->$optName}</textarea>";
                break;
            case 'radio':
                if (!empty($option['options'])) {
                    $value = $this->getAdminMetaValue($optName, $option);
                    $html .= "<ul class='rtcl-radio-list radio horizontal'>";
                    foreach ($option['options'] as $optId => $opt) {
                        $checked = $value == $optId ? " checked='checked'" : '';
                        $html .= "<li class='rtcl-radio-item'><label for='{$id}-{$opt}'><input type='radio' id='{$id}-{$opt}' {$checked} name='rtcl[fields][{$this->_field_id}][{$optName}]' value='{$optId}'> {$opt}</label></li>";
                    }
                    $html .= "</ul>";
                }
                break;
            default:
                $html .= "<input $placeholder id='{$id}' value='{$this->$optName}' class='widefat {$class}' type='text' name='rtcl[fields][{$this->_field_id}][{$optName}]'>";
                break;

        }
    
        return $html;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->_type;
    }

    /**
     * @return mixed
     */
    public function getLabel() {
        return $this->_label;
    }

    /**
     * @return mixed
     */
    public function getSlug() {
        return $this->_slug;
    }

    /**
     * @return mixed
     */
    public function getPlaceholder() {
        return $this->_placeholder;
    }

    /**
     * @return mixed
     */
    public function getDescription() {
        return $this->_description;
    }

    /**
     * @return mixed
     */
    public function getMessage() {
        return $this->_message;
    }

    /**
     * @return array
     */
    public function getOptions() {
        return $this->_options;
    }

    /**
     * @return mixed
     */
    public function getRequired() {
        return $this->_required;
    }

    /**
     * @return mixed
     */
    public function isSearchable() {
        return $this->_searchable;
    }

    /**
     * @return mixed
     */
    public function getListable() {
        return $this->_listable;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue() {
        $default_value = null;
        if (in_array($this->getType(), array('checkbox', 'select', 'radio'))) {
            $options = $this->getOptions();
            if ($this->getType() == 'checkbox') {
                $default_value = !empty($options['default']) && is_array($options['default']) ? $options['default'] : array();
            } else {
                $default_value = !empty($options['default']) ? trim($options['default']) : null;
            }
        } else {
            $default_value = $this->_default_value;
        }

        return $default_value;
    }

    /**
     * @return mixed
     */
    public function getValidation() {
        return $this->_validation;
    }

    /**
     * @return mixed
     */
    public function getValidationMessage() {
        return $this->_validation_message;
    }

    /**
     * @return mixed
     */
    public function getMin() {
        return $this->_min;
    }

    /**
     * @return mixed
     */
    public function getMax() {
        return $this->_max;
    }

    /**
     * @return mixed
     */
    public function getStepSize() {
        return $this->_step_size;
    }

    /**
     * @return mixed
     */
    public function getFieldId() {
        return $this->_field_id;
    }

    /**
     * @return mixed
     */
    public function getRows() {
        return $this->_rows;
    }

    /**
     * @return mixed
     */
    public function getTarget() {
        return $this->_target;
    }

    /**
     * @return mixed
     */
    public function getNofollow() {
        return $this->_nofollow;
    }

    /**
     * @return mixed
     */
    public function getMetaKey() {
        return $this->_meta_key;
    }

    public function getDateRangeMetaKey($key) {
        return $this->getMetaKey() . '_' . $key;
    }

    public function getSanitizedValue($value) {
        switch ($this->getType()) {
            case 'textarea' :
                $value = esc_textarea($value);
                break;
            case 'select' :
            case 'radio'  :
            case 'text' :
                $value = sanitize_text_field($value);
                break;
            case 'checkbox' :
                $value = is_array($value) ? $value : array();
                $value = array_map('esc_attr', $value);
                break;
            case 'url' :
                $value = esc_url_raw($value);
                break;
            case 'date' :
                $value = $this->sanitize_date_field($value);
                break;
            default :
                $value = sanitize_text_field($value);
        }

        return $value;
    }

    public function saveSanitizedValue($post_id, $value) {
        $post_id = $post_id ? absint($post_id) : get_the_ID();
        $value = $this->getSanitizedValue($value);
        switch ($this->getType()) {
            case 'checkbox':
                delete_post_meta($post_id, $this->getMetaKey());
                if (!empty($value) && is_array($value)) {
                    foreach ($value as $val) {
                        if ($val) {
                            add_post_meta($post_id, $this->getMetaKey(), $val);
                        }
                    }
                }
                break;
            case 'date':
                if (is_array($value) && !empty($value)) {
                    foreach ($value as $key => $v) {
                        update_post_meta($post_id, $this->getDateRangeMetaKey($key), $v);
                    }
                } else {
                    update_post_meta($post_id, $this->getMetaKey(), $value);
                }
                break;
            default:
                update_post_meta($post_id, $this->getMetaKey(), $value);
                break;
        }
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getDateFullFormat($type = '') {
        $date_type = $this->getDateType();
        $format = [];
        if ($date_type == 'date' || $date_type == 'date_range') {
            $format[] = $this->getDateFormat();
        }
        if ($date_type == 'date_time' || $date_type == 'date_time_range') {
            $format[] = $this->getDateFormat();
            $format[] = $this->getDateTimeFormat();
        }
        $format = array_filter($format);
        $format = implode(' ', $format);
        $format = $format ? $format : 'Y-d-m';
        if ($type == 'js') {
            $js_options = Options::get_date_js_format_placeholder();
            $find = array_keys($js_options);
            $replace = array_values($js_options);
            $format = str_replace($find, $replace, $format);
        }

        return apply_filters('rtcl_custom_field_date_full_format', $format, $this);
    }

    public function getDateSaveFullFormat() {
        $date_save_format = Functions::get_custom_field_save_date_format();
        $date_type = $this->getDateType();
        if ($date_type == 'date_time' || $date_type == 'date_time_range') {
            $date_save_format = implode(' ', $date_save_format);
        } else {
            $date_save_format = $date_save_format['date'];
        }

        return $date_save_format;
    }

    /**
     * @param       $value
     * @param array $data
     *
     * @return string|string[]
     */
    public function sanitize_date_field($value, $data = []) {
        $date_type = $this->getDateType();
        $input_date_format = $this->getDateFullFormat();
        $save_date_format = $this->getDateSaveFullFormat();

        $date_range = explode(' - ', $value);
        $formatted_date = '';
        $range = in_array($date_type, array('date_range', 'date_time_range'));
        if (!empty($data)) {
            $range = isset($data['range']) ? $data['range'] : $range;
            $input_date_format = isset($data['input_date_format']) ? $data['input_date_format'] : $input_date_format;
            $save_date_format = isset($data['save_date_format']) ? $data['save_date_format'] : $save_date_format;
        }

        try {
            if ($range) {
                $start_date = $end_date = '';
                if (isset($date_range[0]) && !empty($date_range[0])) {
                    $date = DateTime::createFromFormat($input_date_format, $date_range[0]);
                    $start_date = $date->format($save_date_format);
                }
                if (isset($date_range[1]) && !empty($date_range[1])) {
                    $date = DateTime::createFromFormat($input_date_format, $date_range[1]);
                    $end_date = $date->format($save_date_format);
                }
                $formatted_date = [
                    'start' => $start_date,
                    'end'   => $end_date
                ];

            } else {
                if (isset($date_range[0]) && !empty($date_range[0])) {
                    $date = DateTime::createFromFormat($input_date_format, $date_range[0]);
                    $formatted_date = $date->format($save_date_format);
                }
            }
        } catch (\Exception $e) {
            $formatted_date = $range ? ['start' => '', 'end' => ''] : '';
        }

        return $formatted_date;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function getDateFieldOptions($data = array()) {
        $date_type = $this->getDateType();
        $js_format = $this->getDateFullFormat('js');
        $options = wp_parse_args($data, [
            'singleDatePicker' => $date_type == 'date' || $date_type == 'date_time',
            'timePicker'       => $date_type == 'date_time' || $date_type == 'date_time_range',
            'timePicker24Hour' => false !== strpos($js_format, 'HH:mm'),
            'locale'           => [
                'format' => $js_format
            ]
        ]);

        return apply_filters('rtcl_custom_field_date_options', $options, $this);
    }

    /**
     * @return mixed
     */
    public function getDateType() {
        return $this->_date_type;
    }

    /**
     * @return mixed
     */
    public function getDateFormat() {
        return $this->_date_format;
    }

    /**
     * @return mixed
     */
    public function getDateTimeFormat() {
        return $this->_date_time_format;
    }

    /**
     * @return mixed
     */
    public function getDateSearchableType() {
        return $this->_date_searchable_type;
    }


}