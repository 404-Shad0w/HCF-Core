<?php

/*
 * A PocketMine-MP plugin that implements Hard Core Factions.
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 *
 * @author JkqzDev
 */

declare(strict_types=1);

namespace hcf\faction\command;

use hcf\faction\command\subcommand\FactionLockClaimCommand;
use hcf\faction\command\subcommand\FactionAnnounceCommand;
use hcf\faction\command\subcommand\FactionMapCommand;
use hcf\faction\command\subcommand\FactionCampCommand;
use hcf\faction\command\subcommand\admin\FactionAddDtrCommand;
use hcf\faction\command\subcommand\admin\FactionAddPointCommand;
use hcf\faction\command\subcommand\admin\FactionAddStrikeCommand;
use hcf\faction\command\subcommand\admin\FactionForceDisbandCommand;
use hcf\faction\command\subcommand\admin\FactionOpClaimCommand;
use hcf\faction\command\subcommand\admin\FactionRemoveDtrCommand;
use hcf\faction\command\subcommand\admin\FactionRemovePointCommand;
use hcf\faction\command\subcommand\admin\FactionRemoveStrikeCommand;
use hcf\faction\command\subcommand\FactionUpgradeCommand;
use hcf\faction\command\subcommand\FactionAcceptInviteCommand;
use hcf\faction\command\subcommand\FactionChatCommand;
use hcf\faction\command\subcommand\FactionClaimCommand;
use hcf\faction\command\subcommand\FactionCreateCommand;
use hcf\faction\command\subcommand\FactionDemoteCommand;
use hcf\faction\command\subcommand\FactionDepositCommand;
use hcf\faction\command\subcommand\FactionDisbandCommand;
use hcf\faction\command\subcommand\FactionFocusCommand;
use hcf\faction\command\subcommand\FactionHelpCommand;
use hcf\faction\command\subcommand\FactionHomeCommand;
use hcf\faction\command\subcommand\FactionInviteCommand;
use hcf\faction\command\subcommand\FactionKickCommand;
use hcf\faction\command\subcommand\FactionLeaveCommand;
use hcf\faction\command\subcommand\FactionListCommand;
use hcf\faction\command\subcommand\FactionPromoteCommand;
use hcf\faction\command\subcommand\FactionRallyCommand;
use hcf\faction\command\subcommand\FactionSetHomeCommand;
use hcf\faction\command\subcommand\FactionStuckCommand;
use hcf\faction\command\subcommand\FactionTopCommand;
use hcf\faction\command\subcommand\FactionUnclaimCommand;
use hcf\faction\command\subcommand\FactionUnfocusCommand;
use hcf\faction\command\subcommand\FactionUnrallyCommand;
use hcf\faction\command\subcommand\FactionWhoCommand;
use hcf\faction\command\subcommand\FactionWithdrawCommand;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function array_shift;
use function count;

final class FactionCommand extends Command {

	/**
	 * @param FactionSubcommand[] $subcommands
	 * @param FactionSubcommand[] $aliasesSubcommands
	 */
	public function __construct(
		private array $subcommands = [],
		private array $aliasesSubcommands = []
	) {
		parent::__construct(name: 'faction', description: 'Command for factions', aliases: ['f']);
        $this->setPermission('faction.command.use');

		// Admin commands
		$this->addSubcommand(new FactionForceDisbandCommand());
        $this->addSubcommand(new FactionAddDtrCommand());
		$this->addSubcommand(new FactionAddPointCommand());
        $this->addSubcommand(new FactionRemoveDtrCommand());
		$this->addSubcommand(new FactionRemovePointCommand());
		$this->addSubcommand(new FactionAddStrikeCommand());
		$this->addSubcommand(new FactionRemoveStrikeCommand());
		$this->addSubcommand(new FactionOpClaimCommand());

		// Normal commands
        $this->addSubcommand(new FactionLockClaimCommand());
		$this->addSubcommand(new FactionAcceptInviteCommand());
        $this->addSubcommand(new FactionAnnounceCommand());
        $this->addSubcommand(new FactionMapCommand());
        //$this->addSubcommand(new FactionUpgradeCommand());
		$this->addSubcommand(new FactionClaimCommand());
        $this->addSubcommand(new FactionCampCommand());
		$this->addSubcommand(new FactionCreateCommand());
		$this->addSubcommand(new FactionChatCommand());
		$this->addSubcommand(new FactionDemoteCommand());
		$this->addSubcommand(new FactionDepositCommand());
		$this->addSubcommand(new FactionDisbandCommand());
		$this->addSubcommand(new FactionFocusCommand());
		$this->addSubcommand(new FactionHomeCommand());
		$this->addSubcommand(new FactionInviteCommand());
		$this->addSubcommand(new FactionKickCommand());
		$this->addSubcommand(new FactionLeaveCommand());
		$this->addSubcommand(new FactionListCommand());
		$this->addSubcommand(new FactionPromoteCommand());
		$this->addSubcommand(new FactionRallyCommand());
		$this->addSubcommand(new FactionSetHomeCommand());
		$this->addSubcommand(new FactionStuckCommand());
		$this->addSubcommand(new FactionTopCommand());
		$this->addSubcommand(new FactionUnclaimCommand());
		$this->addSubcommand(new FactionUnfocusCommand());
		$this->addSubcommand(new FactionUnrallyCommand());
		$this->addSubcommand(new FactionWhoCommand());
		$this->addSubcommand(new FactionWithdrawCommand());
		$this->addSubcommand(new FactionHelpCommand($this->subcommands));
	}

	public function addSubcommand(FactionSubcommand $subcommand, bool $override = false) : void {
		if (!$override && isset($this->subcommands[$subcommand->getName()])) {
			throw new RuntimeException('Faction subcommand already exists');
		}
		$this->subcommands[$subcommand->getName()] = $subcommand;

		if ($subcommand->getAlias() !== null) {
			$this->aliasesSubcommands[$subcommand->getAlias()] = $subcommand;
		}
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (count($args) < 1) {
			$sender->sendMessage(TextFormat::colorize('&cUse /faction help'));
			return;
		}
		$name = $args[0];

		if (!isset($this->subcommands[$name]) && !isset($this->aliasesSubcommands[$name])) {
			$sender->sendMessage(TextFormat::colorize('&cFaction subcommand dont exists.'));
			return;
		}
		$subcommand = $this->subcommands[$name] ?? $this->aliasesSubcommands[$name];

		if ($subcommand->getPermission() !== null && !$this->testPermission($sender, $subcommand->getPermission())) {
			return;
		}
		array_shift($args);
		$subcommand->execute($sender, $args);
	}
}
