<?php

final class PhorgeGitHubAuthProvider
  extends PhorgeOAuth2AuthProvider {

  public function getProviderName() {
    return pht('GitHub');
  }

  protected function getProviderConfigurationHelp() {
    $uri = PhorgeEnv::getProductionURI('/');
    $callback_uri = PhorgeEnv::getURI($this->getLoginURI());

    return pht(
      "To configure GitHub OAuth, create a new GitHub Application here:".
      "\n\n".
      "https://github.com/settings/applications/new".
      "\n\n".
      "You should use these settings in your application:".
      "\n\n".
      "  - **URL:** Set this to your full domain with protocol. For this ".
      "    server, the correct value is: `%s`\n".
      "  - **Callback URL**: Set this to: `%s`\n".
      "\n\n".
      "Once you've created an application, copy the **Client ID** and ".
      "**Client Secret** into the fields above.",
      $uri,
      $callback_uri);
  }

  protected function newOAuthAdapter() {
    return new PhutilGitHubAuthAdapter();
  }

  protected function getLoginIcon() {
    return 'Github';
  }

  public function getLoginURI() {
    // TODO: Clean this up. See PhorgeAuthOldOAuthRedirectController.
    return '/oauth/github/login/';
  }

}
