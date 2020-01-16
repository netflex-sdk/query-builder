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
    $reflector = new ReflectionClass($subject);
    $traits = $reflector->getTraits();

    while ($reflector) {
      $traits = array_merge($traits, $reflector->getTraits());
      $reflector = $reflector->getParentClass();
    }

    if ($hasTrait = array_key_exists($trait, $traits)) {
      return $hasTrait;
    }

    return false;
  }
}
