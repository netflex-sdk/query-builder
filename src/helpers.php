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
