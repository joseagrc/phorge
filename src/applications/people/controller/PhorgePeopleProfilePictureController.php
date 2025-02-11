<?php

final class PhorgePeopleProfilePictureController
  extends PhorgePeopleProfileController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $this->getViewer();
    $id = $request->getURIData('id');

    $user = id(new PhorgePeopleQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->needProfileImage(true)
      ->requireCapabilities(
        array(
          PhorgePolicyCapability::CAN_VIEW,
          PhorgePolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$user) {
      return new Aphront404Response();
    }

    $this->setUser($user);
    $name = $user->getUserName();

    $done_uri = '/p/'.$name.'/';

    $supported_formats = PhorgeFile::getTransformableImageFormats();
    $e_file = true;
    $errors = array();

    if ($request->isFormPost()) {
      $phid = $request->getStr('phid');
      $is_default = false;
      if ($phid == PhorgePHIDConstants::PHID_VOID) {
        $phid = null;
        $is_default = true;
      } else if ($phid) {
        $file = id(new PhorgeFileQuery())
          ->setViewer($viewer)
          ->withPHIDs(array($phid))
          ->executeOne();
      } else {
        if ($request->getFileExists('picture')) {
          $file = PhorgeFile::newFromPHPUpload(
            $_FILES['picture'],
            array(
              'authorPHID' => $viewer->getPHID(),
              'canCDN' => true,
            ));
        } else {
          $e_file = pht('Required');
          $errors[] = pht(
            'You must choose a file when uploading a new profile picture.');
        }
      }

      if (!$errors && !$is_default) {
        if (!$file->isTransformableImage()) {
          $e_file = pht('Not Supported');
          $errors[] = pht(
            'This server only supports these image formats: %s.',
            implode(', ', $supported_formats));
        } else {
          $xform = PhorgeFileTransform::getTransformByKey(
            PhorgeFileThumbnailTransform::TRANSFORM_PROFILE);
          $xformed = $xform->executeTransform($file);
        }
      }

      if (!$errors) {
        if ($is_default) {
          $user->setProfileImagePHID(null);
        } else {
          $user->setProfileImagePHID($xformed->getPHID());
          $xformed->attachToObject($user->getPHID());
        }
        $user->save();
        return id(new AphrontRedirectResponse())->setURI($done_uri);
      }
    }

    $title = pht('Edit Profile Picture');

    $form = id(new PHUIFormLayoutView())
      ->setUser($viewer);

    $default_image = $user->getDefaultProfileImagePHID();
    if ($default_image) {
      $default_image = id(new PhorgeFileQuery())
        ->setViewer($viewer)
        ->withPHIDs(array($default_image))
        ->executeOne();
    }

    if (!$default_image) {
      $default_image = PhorgeFile::loadBuiltin($viewer, 'profile.png');
    }

    $images = array();

    $current = $user->getProfileImagePHID();
    $has_current = false;
    if ($current) {
      $files = id(new PhorgeFileQuery())
        ->setViewer($viewer)
        ->withPHIDs(array($current))
        ->execute();
      if ($files) {
        $file = head($files);
        if ($file->isTransformableImage()) {
          $has_current = true;
          $images[$current] = array(
            'uri' => $file->getBestURI(),
            'tip' => pht('Current Picture'),
          );
        }
      }
    }

    $builtins = array(
      'user1.png',
      'user2.png',
      'user3.png',
      'user4.png',
      'user5.png',
      'user6.png',
      'user7.png',
      'user8.png',
      'user9.png',
    );
    foreach ($builtins as $builtin) {
      $file = PhorgeFile::loadBuiltin($viewer, $builtin);
      $images[$file->getPHID()] = array(
        'uri' => $file->getBestURI(),
        'tip' => pht('Builtin Image'),
      );
    }

    // Try to add external account images for any associated external accounts.
    $accounts = id(new PhorgeExternalAccountQuery())
      ->setViewer($viewer)
      ->withUserPHIDs(array($user->getPHID()))
      ->needImages(true)
      ->requireCapabilities(
        array(
          PhorgePolicyCapability::CAN_VIEW,
          PhorgePolicyCapability::CAN_EDIT,
        ))
      ->execute();

    foreach ($accounts as $account) {
      $file = $account->getProfileImageFile();
      if ($account->getProfileImagePHID() != $file->getPHID()) {
        // This is a default image, just skip it.
        continue;
      }

      $config = $account->getProviderConfig();
      $provider = $config->getProvider();

      $tip = pht('Picture From %s', $provider->getProviderName());

      if ($file->isTransformableImage()) {
        $images[$file->getPHID()] = array(
          'uri' => $file->getBestURI(),
          'tip' => $tip,
        );
      }
    }

    $images[PhorgePHIDConstants::PHID_VOID] = array(
      'uri' => $default_image->getBestURI(),
      'tip' => pht('Default Picture'),
    );

    require_celerity_resource('people-profile-css');
    Javelin::initBehavior('phorge-tooltips', array());

    $buttons = array();
    foreach ($images as $phid => $spec) {
      $style = null;
      if (isset($spec['style'])) {
        $style = $spec['style'];
      }
      $button = javelin_tag(
        'button',
        array(
          'class' => 'button-grey profile-image-button',
          'sigil' => 'has-tooltip',
          'meta' => array(
            'tip' => $spec['tip'],
            'size' => 300,
          ),
        ),
        phutil_tag(
          'img',
          array(
            'height' => 50,
            'width' => 50,
            'src' => $spec['uri'],
          )));

      $button = array(
        phutil_tag(
          'input',
          array(
            'type'  => 'hidden',
            'name'  => 'phid',
            'value' => $phid,
          )),
        $button,
      );

      $button = phorge_form(
        $viewer,
        array(
          'class' => 'profile-image-form',
          'method' => 'POST',
        ),
        $button);

      $buttons[] = $button;
    }

    if ($has_current) {
      $form->appendChild(
        id(new AphrontFormMarkupControl())
          ->setLabel(pht('Current Picture'))
          ->setValue(array_shift($buttons)));
    }

    $form->appendChild(
      id(new AphrontFormMarkupControl())
        ->setLabel(pht('Use Picture'))
        ->setValue($buttons));

    $form_box = id(new PHUIObjectBoxView())
      ->setHeaderText($title)
      ->setFormErrors($errors)
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->setForm($form);

    $upload_form = id(new AphrontFormView())
      ->setUser($viewer)
      ->setEncType('multipart/form-data')
      ->appendChild(
        id(new AphrontFormFileControl())
          ->setName('picture')
          ->setLabel(pht('Upload Picture'))
          ->setError($e_file)
          ->setCaption(
            pht('Supported formats: %s', implode(', ', $supported_formats))))
      ->appendChild(
        id(new AphrontFormSubmitControl())
          ->addCancelButton($done_uri)
          ->setValue(pht('Upload Picture')));

    $upload_box = id(new PHUIObjectBoxView())
      ->setHeaderText(pht('Upload New Picture'))
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->setForm($upload_form);

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb(pht('Edit Profile Picture'));
    $crumbs->setBorder(true);

    $nav = $this->newNavigation(
      $user,
      PhorgePeopleProfileMenuEngine::ITEM_MANAGE);

    $header = $this->buildProfileHeader();

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->addClass('project-view-home')
      ->addClass('project-view-people-home')
      ->setFooter(array(
        $form_box,
        $upload_box,
      ));

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->setNavigation($nav)
      ->appendChild($view);
  }
}
