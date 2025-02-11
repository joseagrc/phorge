<?php

/**
 * Convenience class which simplifies the implementation of
 * @{interface:PhorgeCustomFieldInterface} by obscuring the details of how
 * custom fields are stored.
 *
 * Generally, you should not use this class directly. It is used by
 * @{class:PhorgeCustomField} to manage field storage on objects.
 */
final class PhorgeCustomFieldAttachment extends Phobject {

  private $lists = array();

  public function addCustomFieldList($role, PhorgeCustomFieldList $list) {
    $this->lists[$role] = $list;
    return $this;
  }

  public function getCustomFieldList($role) {
    if (empty($this->lists[$role])) {
      throw new PhorgeCustomFieldNotAttachedException(
        pht(
          "Role list '%s' is not available!",
          $role));
    }
    return $this->lists[$role];
  }

}
