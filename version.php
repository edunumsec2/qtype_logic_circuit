<?php
/**
 * logic question type version information.
 *
 * @package    qtype_logiccircuit
 * @copyright  2025 Groupe Modulo
 * @license    CC BY-NC-SA
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'qtype_logiccircuit';
$plugin->version  = 2026042101;
$plugin->requires = 2022040100;  // Moodle 4.0.
$plugin->supported = [400, 530];
$plugin->maturity  = MATURITY_STABLE;
$plugin->release  = 'v1.0.0';
