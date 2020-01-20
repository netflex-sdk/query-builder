<?php

if (!function_exists('has_trait')) {
  /**
   * Check if a class has a trait
   *
   * @param string $subject
   * @param string $trait
   * @return bool
   */
  function has_trait(string $subject, string $trait)
  {
    return in_array($trait, class_uses_recursive($subject), true);
  }
}

if (!function_exists('uuid')) {
  /**
   * Generates a unique id
   *
   * @return string
   */
  function uuid()
  {
    mt_srand(time());

    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff)
    );
  }
}
