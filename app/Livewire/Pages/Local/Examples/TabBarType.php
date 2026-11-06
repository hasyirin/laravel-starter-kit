<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Local\Examples;

enum TabBarType: string
{
    case Tabs = 'tabs';
    case Card = 'card';
    case Navlist = 'navlist';
    case Navbar = 'navbar';
}
