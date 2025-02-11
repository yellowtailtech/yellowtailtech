<?php

namespace WPFormsFormAbandonment\Migrations;

use WPForms\Migrations\UpgradeBase;
use WPForms\Tasks\Actions\Migration173Task;

/**
 * Class Form Abandonment addon v1.7.0 upgrade.
 *
 * @since 1.7.0
 *
 * @noinspection PhpUnused
 */
class Upgrade170 extends UpgradeBase {

	/**
	 * Run upgrade.
	 *
	 * @since 1.7.0
	 *
	 * @return bool|null Upgrade result:
	 *                   true  - the upgrade completed successfully,
	 *                   false - in the case of failure,
	 *                   null  - upgrade started but not yet finished (background task).
	 */
	public function run() {

		return $this->run_async( Migration173Task::class );
	}
}
