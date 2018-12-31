<?php

namespace {

    use SilverStripe\CMS\Controllers\ContentController;
    use SilverStripe\Control\Director;
    use SilverStripe\Core\Convert;
    use SilverStripe\SiteConfig\SiteConfig;
    use SilverStripe\View\ArrayData;
    use SilverStripe\View\Requirements;

    class PageController extends ContentController
    {
        /**
         * An array of actions that can be accessed via a request. Each array element should be an action name, and the
         * permissions or conditions required to allow the user to access it.
         *
         * <code>
         * [
         *     'action', // anyone can access this action
         *     'action' => true, // same as above
         *     'action' => 'ADMIN', // you must have ADMIN permissions to access this action
         *     'action' => '->checkAction' // you can only access this action if $this->checkAction() returns true
         * ];
         * </code>
         *
         * @var array
         */
        private static $allowed_actions = [];

        protected function init()
        {
            parent::init();
            // You can include any CSS or JS required by your project here.
            // See: https://docs.silverstripe.org/en/developer_guides/templates/requirements/
            Requirements::themedCSS('styles');
            if (SilverStripe\Control\Director::isDev()) {
                SilverStripe\View\SSViewer::config()->set('source_file_comments', true);
            }
        }

        public function MetaTags($includeTitle = true)
        {
            $tags = parent::MetaTags($includeTitle);

            if ($this->ConanicalURL) {
                $tags .= "<link rel=\"canonical\" href=\"" . Convert::raw2att($this->ConanicalURL) . "\" />\n";
            } elseif (SiteConfig::current_site_config()->ConanicalURL) {
                $tags .= "<link rel=\"canonical\" href=\"";
                $tags .= Convert::raw2att(SiteConfig::current_site_config()->ConanicalURL) . "\" />\n";
            }

            if ($this->MetaKeywords) {
                $tags .= "<meta name=\"keywords\" content=\"" . Convert::raw2att($this->MetaKeywords) . "\" />\n";
            }
            if ($this->ExtraMeta) {
                $tags .= $this->ExtraMeta . "\n";
            }

            if ($this->URLSegment == 'home' && SiteConfig::current_site_config()->GoogleSiteVerificationCode) {
                $tags .= '<meta name="google-site-verification" content="'
                        . SiteConfig::current_site_config()->GoogleSiteVerificationCode . '" />' . "\n";
            }

            // prevent bots from spidering the site whilest in dev.
            if (!Director::isLive()) {
                $tags .= "<meta name=\"robots\" content=\"noindex, nofollow, noarchive\" />\n";
            } elseif (!empty($this->MetaRobots)) {
                $tags .= "<meta name=\"robots\" content=\"$this->MetaRobots\" />\n";
            }

            $this->extend('MetaTags', $tags);

            return $tags;
        }

        public function getOGTwitter()
        {
            $site_config    =   SiteConfig::current_site_config();
            if (!empty($this->OGType) || !empty($site_config->OGType)) {
                $data   =   [
                    'OGType'                =>  !empty($this->OGType) ?
                                                $this->OGType :
                                                $site_config->OGType,
                    'AbsoluteLink'          =>  $this->AbsoluteLink(),
                    'OGTitle'               =>  !empty($this->OGTitle) ?
                                                $this->OGTitle :
                                                $this->Title,
                    'OGDescription'         =>  !empty($this->OGDescription) ?
                                                $this->OGDescription :
                                                $site_config->OGDescription,
                    'OGImage'               =>  !empty($this->OGImage()->exists()) ?
                                                $this->OGImage() :
                                                $site_config->OGImage(),
                    'OGImageLarge'          =>  !empty($this->OGImageLarge()->exists()) ?
                                                $this->OGImageLarge() :
                                                $site_config->OGImageLarge(),
                    'TwitterCard'           =>  !empty($this->TwitterCard) ?
                                                $this->TwitterCard :
                                                $site_config->TwitterCard,
                    'TwitterTitle'          =>  !empty($this->TwitterTitle) ?
                                                $this->TwitterTitle :
                                                $this->Title,
                    'TwitterDescription'    =>  !empty($this->TwitterDescription) ?
                                                $this->TwitterDescription :
                                                $site_config->TwitterDescription,
                    'TwitterImageLarge'     =>  !empty($this->TwitterImageLarge()->exists()) ?
                                                $this->TwitterImageLarge() :
                                                $site_config->TwitterImageLarge(),
                    'TwitterImage'          =>  !empty($this->TwitterImage()->exists()) ?
                                                $this->TwitterImage() :
                                                $site_config->TwitterImage(),
                ];

                return ArrayData::create($data);
            }

            return null;
        }
    }
}
