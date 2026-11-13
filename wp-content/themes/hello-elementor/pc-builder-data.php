<?php
/**
 * PC Builder catalog with indicative Indian retail pricing.
 */

$cpu_rows = [
	[ 'Intel Core i3-12100', 15900, 4, 8, 60, 89, 'Intel 12th Gen', true ],
	[ 'Intel Core i3-12100F', 10650, 4, 8, 58, 89, 'Intel 12th Gen', false ],
	[ 'Intel Core i3-12100T', 17000, 4, 8, 35, 69, 'Intel 12th Gen', true ],
	[ 'Intel Core i3-12300', 19000, 4, 8, 60, 89, 'Intel 12th Gen', true ],
	[ 'Intel Core i3-12300T', 19000, 4, 8, 35, 69, 'Intel 12th Gen', true ],
	[ 'Intel Core i5-12400', 18500, 6, 12, 65, 117, 'Intel 12th Gen', true ],
	[ 'Intel Core i5-12400F', 10650, 6, 12, 65, 117, 'Intel 12th Gen', false ],
	[ 'Intel Core i5-12400T', 20000, 6, 12, 35, 74, 'Intel 12th Gen', true ],
	[ 'Intel Core i5-12500', 22000, 6, 12, 65, 117, 'Intel 12th Gen', true ],
	[ 'Intel Core i5-12500T', 23000, 6, 12, 35, 74, 'Intel 12th Gen', true ],
	[ 'Intel Core i5-12600', 23000, 6, 12, 65, 117, 'Intel 12th Gen', true ],
	[ 'Intel Core i5-12600T', 23000, 6, 12, 35, 74, 'Intel 12th Gen', true ],
	[ 'Intel Core i5-12600K', 25000, 10, 16, 125, 150, 'Intel 12th Gen', true ],
	[ 'Intel Core i5-12600KF', 23000, 10, 16, 125, 150, 'Intel 12th Gen', false ],
	[ 'Intel Core i7-12700', 30000, 12, 20, 65, 180, 'Intel 12th Gen', true ],
	[ 'Intel Core i7-12700F', 28000, 12, 20, 65, 180, 'Intel 12th Gen', false ],
	[ 'Intel Core i7-12700T', 30000, 12, 20, 35, 99, 'Intel 12th Gen', true ],
	[ 'Intel Core i7-12700K', 34000, 12, 20, 125, 190, 'Intel 12th Gen', true ],
	[ 'Intel Core i7-12700KF', 32000, 12, 20, 125, 190, 'Intel 12th Gen', false ],
	[ 'Intel Core i9-12900', 42000, 16, 24, 65, 202, 'Intel 12th Gen', true ],
	[ 'Intel Core i9-12900F', 40000, 16, 24, 65, 202, 'Intel 12th Gen', false ],
	[ 'Intel Core i9-12900K', 47000, 16, 24, 125, 241, 'Intel 12th Gen', true ],
	[ 'Intel Core i9-12900KF', 45000, 16, 24, 125, 241, 'Intel 12th Gen', false ],
	[ 'Intel Core i9-12900KS', 52000, 16, 24, 150, 241, 'Intel 12th Gen', true ],
	[ 'Intel Core i9-12900T', 44000, 16, 24, 35, 106, 'Intel 12th Gen', true ],
	[ 'Intel Core i3-13100', 17000, 4, 8, 60, 89, 'Intel 13th Gen', true ],
	[ 'Intel Core i3-13100F', 12500, 4, 8, 58, 89, 'Intel 13th Gen', false ],
	[ 'Intel Core i3-13100T', 18000, 4, 8, 35, 69, 'Intel 13th Gen', true ],
	[ 'Intel Core i5-13400', 23000, 10, 16, 65, 148, 'Intel 13th Gen', true ],
	[ 'Intel Core i5-13400F', 19000, 10, 16, 65, 148, 'Intel 13th Gen', false ],
	[ 'Intel Core i5-13400T', 24000, 10, 16, 35, 82, 'Intel 13th Gen', true ],
	[ 'Intel Core i5-13500', 27000, 14, 20, 65, 154, 'Intel 13th Gen', true ],
	[ 'Intel Core i5-13500T', 28000, 14, 20, 35, 92, 'Intel 13th Gen', true ],
	[ 'Intel Core i5-13600K', 30000, 14, 20, 125, 181, 'Intel 13th Gen', true ],
	[ 'Intel Core i5-13600KF', 28000, 14, 20, 125, 181, 'Intel 13th Gen', false ],
	[ 'Intel Core i7-13700', 39000, 16, 24, 65, 219, 'Intel 13th Gen', true ],
	[ 'Intel Core i7-13700F', 37000, 16, 24, 65, 219, 'Intel 13th Gen', false ],
	[ 'Intel Core i7-13700T', 39000, 16, 24, 35, 106, 'Intel 13th Gen', true ],
	[ 'Intel Core i7-13700K', 42000, 16, 24, 125, 253, 'Intel 13th Gen', true ],
	[ 'Intel Core i7-13700KF', 40000, 16, 24, 125, 253, 'Intel 13th Gen', false ],
	[ 'Intel Core i9-13900', 50000, 24, 32, 65, 219, 'Intel 13th Gen', true ],
	[ 'Intel Core i9-13900F', 48000, 24, 32, 65, 219, 'Intel 13th Gen', false ],
	[ 'Intel Core i9-13900K', 55000, 24, 32, 125, 253, 'Intel 13th Gen', true ],
	[ 'Intel Core i9-13900KF', 53000, 24, 32, 125, 253, 'Intel 13th Gen', false ],
	[ 'Intel Core i9-13900KS', 65000, 24, 32, 150, 253, 'Intel 13th Gen', true ],
	[ 'Intel Core i9-13900T', 52000, 24, 32, 35, 106, 'Intel 13th Gen', true ],
	[ 'Intel Core i3-14100', 18000, 4, 8, 60, 110, 'Intel 14th Gen', true ],
	[ 'Intel Core i3-14100F', 14500, 4, 8, 58, 110, 'Intel 14th Gen', false ],
	[ 'Intel Core i3-14100T', 19000, 4, 8, 35, 69, 'Intel 14th Gen', true ],
	[ 'Intel Core i5-14400', 25000, 10, 16, 65, 148, 'Intel 14th Gen', true ],
	[ 'Intel Core i5-14400F', 21000, 10, 16, 65, 148, 'Intel 14th Gen', false ],
	[ 'Intel Core i5-14400T', 26000, 10, 16, 35, 82, 'Intel 14th Gen', true ],
	[ 'Intel Core i5-14500', 29000, 14, 20, 65, 154, 'Intel 14th Gen', true ],
	[ 'Intel Core i5-14500T', 30000, 14, 20, 35, 92, 'Intel 14th Gen', true ],
	[ 'Intel Core i5-14600K', 33000, 14, 20, 125, 181, 'Intel 14th Gen', true ],
	[ 'Intel Core i5-14600KF', 31000, 14, 20, 125, 181, 'Intel 14th Gen', false ],
	[ 'Intel Core i7-14700', 43000, 20, 28, 65, 219, 'Intel 14th Gen', true ],
	[ 'Intel Core i7-14700F', 41000, 20, 28, 65, 219, 'Intel 14th Gen', false ],
	[ 'Intel Core i7-14700T', 43000, 20, 28, 35, 106, 'Intel 14th Gen', true ],
	[ 'Intel Core i7-14700K', 47000, 20, 28, 125, 253, 'Intel 14th Gen', true ],
	[ 'Intel Core i7-14700KF', 45000, 20, 28, 125, 253, 'Intel 14th Gen', false ],
	[ 'Intel Core i9-14900', 58000, 24, 32, 65, 219, 'Intel 14th Gen', true ],
	[ 'Intel Core i9-14900F', 55000, 24, 32, 65, 219, 'Intel 14th Gen', false ],
	[ 'Intel Core i9-14900K', 62000, 24, 32, 125, 253, 'Intel 14th Gen', true ],
	[ 'Intel Core i9-14900KF', 60000, 24, 32, 125, 253, 'Intel 14th Gen', false ],
	[ 'Intel Core i9-14900KS', 75000, 24, 32, 150, 253, 'Intel 14th Gen', true ],
	[ 'Intel Core i9-14900T', 60000, 24, 32, 35, 106, 'Intel 14th Gen', true ],
	[ 'AMD Ryzen 5 7500F', 17000, 6, 12, 65, 88, 'AMD Ryzen 7000', false ],
	[ 'AMD Ryzen 5 7600', 20000, 6, 12, 65, 88, 'AMD Ryzen 7000', true ],
	[ 'AMD Ryzen 5 7600X', 22000, 6, 12, 105, 142, 'AMD Ryzen 7000', true ],
	[ 'AMD Ryzen 5 7600X3D', 30000, 6, 12, 65, 88, 'AMD Ryzen 7000', true ],
	[ 'AMD Ryzen 7 7700', 27000, 8, 16, 65, 88, 'AMD Ryzen 7000', true ],
	[ 'AMD Ryzen 7 7700X', 30000, 8, 16, 105, 142, 'AMD Ryzen 7000', true ],
	[ 'AMD Ryzen 7 7800X3D', 38000, 8, 16, 120, 162, 'AMD Ryzen 7000', true ],
	[ 'AMD Ryzen 9 7900', 38000, 12, 24, 65, 88, 'AMD Ryzen 7000', true ],
	[ 'AMD Ryzen 9 7900X', 42000, 12, 24, 170, 230, 'AMD Ryzen 7000', true ],
	[ 'AMD Ryzen 9 7900X3D', 47000, 12, 24, 120, 162, 'AMD Ryzen 7000', true ],
	[ 'AMD Ryzen 9 7950X', 55000, 16, 32, 170, 230, 'AMD Ryzen 7000', true ],
	[ 'AMD Ryzen 9 7950X3D', 62000, 16, 32, 120, 162, 'AMD Ryzen 7000', true ],
	[ 'AMD Ryzen 5 8400F', 16000, 6, 12, 65, 88, 'AMD Ryzen 8000', false ],
	[ 'AMD Ryzen 3 8300G', 14000, 4, 8, 65, 88, 'AMD Ryzen 8000G', true ],
	[ 'AMD Ryzen 5 8500G', 18000, 6, 12, 65, 88, 'AMD Ryzen 8000', true ],
	[ 'AMD Ryzen 5 8600G', 22000, 6, 12, 65, 88, 'AMD Ryzen 8000', true ],
	[ 'AMD Ryzen 7 8700F', 25000, 8, 16, 65, 88, 'AMD Ryzen 8000', false ],
	[ 'AMD Ryzen 7 8700G', 29000, 8, 16, 65, 88, 'AMD Ryzen 8000', true ],
	[ 'AMD Ryzen 5 9600X', 25000, 6, 12, 65, 88, 'AMD Ryzen 9000', true ],
	[ 'AMD Ryzen 7 9700X', 33000, 8, 16, 65, 88, 'AMD Ryzen 9000', true ],
	[ 'AMD Ryzen 7 9800X3D', 48000, 8, 16, 120, 162, 'AMD Ryzen 9000', true ],
	[ 'AMD Ryzen 9 9900X', 43000, 12, 24, 120, 162, 'AMD Ryzen 9000', true ],
	[ 'AMD Ryzen 9 9900X3D', 55000, 12, 24, 120, 162, 'AMD Ryzen 9000', true ],
	[ 'AMD Ryzen 9 9950X', 58000, 16, 32, 170, 230, 'AMD Ryzen 9000', true ],
	[ 'AMD Ryzen 9 9950X3D', 72000, 16, 32, 170, 230, 'AMD Ryzen 9000', true ],
];

$cpus = [];
foreach ( $cpu_rows as $index => $row ) {
	[ $name, $price, $cores, $threads, $tdp, $max_power, $group, $has_graphics ] = $row;
	$socket = str_starts_with( $name, 'Intel ' ) ? 'LGA1700' : 'AM5';
	$generation = preg_match( '/(\d{4,5})/', $name, $matches ) ? $matches[1] : '';
	$generation_label = str_starts_with( $name, 'Intel ' )
		? $group
		: $group . ' Series';

	$cpus[] = [
		'id'           => 'cpu_' . ( $index + 1 ),
		'name'         => $name,
		'price'        => $price,
		'socket'       => $socket,
		'generation'   => $generation_label,
		'family'       => str_starts_with( $name, 'Intel ' ) ? 'Intel' : 'AMD',
		'tdp'          => $tdp,
		'max_power'    => $max_power,
		'cores'        => $cores,
		'threads'      => $threads,
		'has_graphics' => $has_graphics,
		'price_url'    => 'https://www.amazon.in/s?k=' . rawurlencode( $name ),
		'specs'        => [
			$generation_label,
			$cores . ' Cores / ' . $threads . ' Threads',
			$tdp . 'W Base TDP',
			$has_graphics ? 'Integrated graphics' : 'Discrete GPU required',
			$socket,
		],
	];
}

$make_component = static function ( $id, $name, $price, $specs, $attributes = [] ) {
	return array_merge(
		[
			'id'        => $id,
			'name'      => $name,
			'price'     => $price,
			'price_url' => 'https://www.amazon.in/s?k=' . rawurlencode( $name ),
			'specs'     => $specs,
		],
		$attributes
	);
};

return [
	'currency'       => 'INR',
	'price_checked'  => '2026-10-24',
	'price_sources'  => [
		[ 'name' => 'MDComputers', 'url' => 'https://mdcomputers.in/catalog/processor' ],
		[ 'name' => 'Amazon India', 'url' => 'https://www.amazon.in/s?k=pc+components' ],
	],
	'categories'     => [
		'cpu' => [
			'category'   => 'CPU (Processor)',
			'description' => 'Search or filter by processor family. AMD uses Ryzen series names (7000, 8000 and 9000), not Intel generation numbers.',
			'filter_key' => 'generation',
			'components' => $cpus,
		],
		'motherboard' => [
			'category'   => 'Motherboard',
			'description' => '*Some processor and motherboard combinations need a BIOS update. Confirm the CPU support list and installed BIOS version with the manufacturer or retailer.',
			'filter_key' => 'socket',
			'components' => [
				$make_component( 'mb_1', 'MSI PRO B760M-A WIFI DDR4', 15900, [ 'LGA1700', 'DDR4', 'Micro-ATX', '12th–14th Gen*' ], [ 'socket' => 'LGA1700', 'ram_type' => 'DDR4', 'form_factor' => 'mATX', 'pcie_slots' => [ 'PCIe 4.0', 'PCIe 5.0' ], 'storage_interfaces' => [ 'M.2 NVMe', 'SATA' ], 'generation_support' => [ 'Intel 12th Gen', 'Intel 13th Gen', 'Intel 14th Gen' ] ] ),
				$make_component( 'mb_2', 'MSI PRO B760M-A WIFI DDR5', 17900, [ 'LGA1700', 'DDR5', 'Micro-ATX', '12th–14th Gen*' ], [ 'socket' => 'LGA1700', 'ram_type' => 'DDR5', 'form_factor' => 'mATX', 'pcie_slots' => [ 'PCIe 4.0', 'PCIe 5.0' ], 'storage_interfaces' => [ 'M.2 NVMe', 'SATA' ], 'generation_support' => [ 'Intel 12th Gen', 'Intel 13th Gen', 'Intel 14th Gen' ] ] ),
				$make_component( 'mb_3', 'ASUS TUF GAMING B760-PLUS WIFI D4', 21900, [ 'LGA1700', 'DDR4', 'ATX', '12th–14th Gen*' ], [ 'socket' => 'LGA1700', 'ram_type' => 'DDR4', 'form_factor' => 'ATX', 'pcie_slots' => [ 'PCIe 4.0', 'PCIe 5.0' ], 'storage_interfaces' => [ 'M.2 NVMe', 'SATA' ], 'generation_support' => [ 'Intel 12th Gen', 'Intel 13th Gen', 'Intel 14th Gen' ] ] ),
				$make_component( 'mb_4', 'MSI MAG B760 TOMAHAWK WIFI DDR5', 25900, [ 'LGA1700', 'DDR5', 'ATX', '12th–14th Gen*' ], [ 'socket' => 'LGA1700', 'ram_type' => 'DDR5', 'form_factor' => 'ATX', 'pcie_slots' => [ 'PCIe 4.0', 'PCIe 5.0' ], 'storage_interfaces' => [ 'M.2 NVMe', 'SATA' ], 'generation_support' => [ 'Intel 12th Gen', 'Intel 13th Gen', 'Intel 14th Gen' ] ] ),
				$make_component( 'mb_5', 'ASUS PRIME Z790-P WIFI DDR5', 28900, [ 'LGA1700', 'DDR5', 'ATX', '12th–14th Gen*' ], [ 'socket' => 'LGA1700', 'ram_type' => 'DDR5', 'form_factor' => 'ATX', 'pcie_slots' => [ 'PCIe 4.0', 'PCIe 5.0' ], 'storage_interfaces' => [ 'M.2 NVMe', 'SATA' ], 'generation_support' => [ 'Intel 12th Gen', 'Intel 13th Gen', 'Intel 14th Gen' ] ] ),
				$make_component( 'mb_6', 'Gigabyte Z790 AORUS ELITE AX', 33900, [ 'LGA1700', 'DDR5', 'ATX', '12th–14th Gen*' ], [ 'socket' => 'LGA1700', 'ram_type' => 'DDR5', 'form_factor' => 'ATX', 'pcie_slots' => [ 'PCIe 4.0', 'PCIe 5.0' ], 'storage_interfaces' => [ 'M.2 NVMe', 'SATA' ], 'generation_support' => [ 'Intel 12th Gen', 'Intel 13th Gen', 'Intel 14th Gen' ] ] ),
				$make_component( 'mb_7', 'MSI PRO B650M-A WIFI', 16900, [ 'AM5', 'DDR5', 'Micro-ATX', 'Ryzen 7000/8000/9000*' ], [ 'socket' => 'AM5', 'ram_type' => 'DDR5', 'form_factor' => 'mATX', 'pcie_slots' => [ 'PCIe 4.0', 'PCIe 5.0' ], 'storage_interfaces' => [ 'M.2 NVMe', 'SATA' ], 'generation_support' => [ 'AMD Ryzen 7000 Series', 'AMD Ryzen 8000 Series', 'AMD Ryzen 8000G Series', 'AMD Ryzen 9000 Series' ] ] ),
				$make_component( 'mb_8', 'Gigabyte B650 AORUS ELITE AX', 22900, [ 'AM5', 'DDR5', 'ATX', 'Ryzen 7000/8000/9000*' ], [ 'socket' => 'AM5', 'ram_type' => 'DDR5', 'form_factor' => 'ATX', 'pcie_slots' => [ 'PCIe 4.0', 'PCIe 5.0' ], 'storage_interfaces' => [ 'M.2 NVMe', 'SATA' ], 'generation_support' => [ 'AMD Ryzen 7000 Series', 'AMD Ryzen 8000 Series', 'AMD Ryzen 8000G Series', 'AMD Ryzen 9000 Series' ] ] ),
				$make_component( 'mb_9', 'ASUS TUF GAMING B650-PLUS WIFI', 24900, [ 'AM5', 'DDR5', 'ATX', 'Ryzen 7000/8000/9000*' ], [ 'socket' => 'AM5', 'ram_type' => 'DDR5', 'form_factor' => 'ATX', 'pcie_slots' => [ 'PCIe 4.0', 'PCIe 5.0' ], 'storage_interfaces' => [ 'M.2 NVMe', 'SATA' ], 'generation_support' => [ 'AMD Ryzen 7000 Series', 'AMD Ryzen 8000 Series', 'AMD Ryzen 8000G Series', 'AMD Ryzen 9000 Series' ] ] ),
				$make_component( 'mb_10', 'MSI MAG X870 TOMAHAWK WIFI', 35900, [ 'AM5', 'DDR5', 'ATX', 'Ryzen 7000/8000/9000*' ], [ 'socket' => 'AM5', 'ram_type' => 'DDR5', 'form_factor' => 'ATX', 'pcie_slots' => [ 'PCIe 4.0', 'PCIe 5.0' ], 'storage_interfaces' => [ 'M.2 NVMe', 'SATA' ], 'generation_support' => [ 'AMD Ryzen 7000 Series', 'AMD Ryzen 8000 Series', 'AMD Ryzen 8000G Series', 'AMD Ryzen 9000 Series' ] ] ),
				$make_component( 'mb_11', 'ASUS ROG STRIX X870E-E GAMING WIFI', 59900, [ 'AM5', 'DDR5', 'ATX', 'Ryzen 7000/8000/9000*' ], [ 'socket' => 'AM5', 'ram_type' => 'DDR5', 'form_factor' => 'ATX', 'pcie_slots' => [ 'PCIe 4.0', 'PCIe 5.0' ], 'storage_interfaces' => [ 'M.2 NVMe', 'SATA' ], 'generation_support' => [ 'AMD Ryzen 7000 Series', 'AMD Ryzen 8000 Series', 'AMD Ryzen 8000G Series', 'AMD Ryzen 9000 Series' ] ] ),
			],
		],
		'gpu' => [
			'category'   => 'Graphics Card (GPU)',
			'components' => [
				$make_component( 'gpu_1', 'NVIDIA GeForce RTX 3050 8GB', 38000, [ '8GB GDDR6', '130W', 'PCIe 4.0' ], [ 'vram' => 8, 'power_req' => 130, 'pcie' => 'PCIe 4.0' ] ),
				$make_component( 'gpu_2', 'NVIDIA GeForce RTX 4060 8GB', 32000, [ '8GB GDDR6', '115W', 'PCIe 4.0' ], [ 'vram' => 8, 'power_req' => 115, 'pcie' => 'PCIe 4.0' ] ),
				$make_component( 'gpu_3', 'NVIDIA GeForce RTX 5060 8GB', 34000, [ '8GB GDDR7', '145W', 'PCIe 5.0' ], [ 'vram' => 8, 'power_req' => 145, 'pcie' => 'PCIe 5.0' ] ),
				$make_component( 'gpu_4', 'NVIDIA GeForce RTX 5060 Ti 16GB', 52000, [ '16GB GDDR7', '180W', 'PCIe 5.0' ], [ 'vram' => 16, 'power_req' => 180, 'pcie' => 'PCIe 5.0' ] ),
				$make_component( 'gpu_5', 'NVIDIA GeForce RTX 5070 12GB', 65000, [ '12GB GDDR7', '250W', 'PCIe 5.0' ], [ 'vram' => 12, 'power_req' => 250, 'pcie' => 'PCIe 5.0' ] ),
				$make_component( 'gpu_6', 'NVIDIA GeForce RTX 5080 16GB', 115000, [ '16GB GDDR7', '360W', 'PCIe 5.0' ], [ 'vram' => 16, 'power_req' => 360, 'pcie' => 'PCIe 5.0' ] ),
				$make_component( 'gpu_7', 'AMD Radeon RX 7600 8GB', 28000, [ '8GB GDDR6', '165W', 'PCIe 4.0' ], [ 'vram' => 8, 'power_req' => 165, 'pcie' => 'PCIe 4.0' ] ),
				$make_component( 'gpu_8', 'AMD Radeon RX 9060 XT 16GB', 45000, [ '16GB GDDR6', '160W', 'PCIe 5.0' ], [ 'vram' => 16, 'power_req' => 160, 'pcie' => 'PCIe 5.0' ] ),
				$make_component( 'gpu_9', 'AMD Radeon RX 9070 XT 16GB', 76000, [ '16GB GDDR6', '304W', 'PCIe 5.0' ], [ 'vram' => 16, 'power_req' => 304, 'pcie' => 'PCIe 5.0' ] ),
			],
		],
		'ram' => [
			'category'   => 'RAM (Memory)',
			'filter_key' => 'type',
			'components' => [
				$make_component( 'ram_1', 'Corsair Vengeance LPX 16GB (2x8GB) DDR4-3200', 3900, [ '16GB', 'DDR4', '3200 MT/s' ], [ 'capacity' => 16, 'type' => 'DDR4', 'speed' => 3200 ] ),
				$make_component( 'ram_2', 'Corsair Vengeance LPX 32GB (2x16GB) DDR4-3200', 6900, [ '32GB', 'DDR4', '3200 MT/s' ], [ 'capacity' => 32, 'type' => 'DDR4', 'speed' => 3200 ] ),
				$make_component( 'ram_3', 'Kingston Fury Beast 16GB (2x8GB) DDR5-5600', 5200, [ '16GB', 'DDR5', '5600 MT/s' ], [ 'capacity' => 16, 'type' => 'DDR5', 'speed' => 5600 ] ),
				$make_component( 'ram_4', 'Kingston Fury Beast 32GB (2x16GB) DDR5-6000', 8900, [ '32GB', 'DDR5', '6000 MT/s' ], [ 'capacity' => 32, 'type' => 'DDR5', 'speed' => 6000 ] ),
				$make_component( 'ram_5', 'G.Skill Flare X5 32GB (2x16GB) DDR5-6000', 9500, [ '32GB', 'DDR5', '6000 MT/s', 'AMD EXPO' ], [ 'capacity' => 32, 'type' => 'DDR5', 'speed' => 6000 ] ),
				$make_component( 'ram_6', 'Corsair Vengeance 64GB (2x32GB) DDR5-6000', 17900, [ '64GB', 'DDR5', '6000 MT/s' ], [ 'capacity' => 64, 'type' => 'DDR5', 'speed' => 6000 ] ),
			],
		],
		'storage' => [
			'category'   => 'Storage (SSD/HDD)',
			'filter_key' => 'type',
			'components' => [
				$make_component( 'storage_1', 'WD Blue SN580 1TB NVMe SSD', 5900, [ '1TB', 'NVMe Gen4', '4150MB/s' ], [ 'capacity' => 1, 'type' => 'NVMe SSD', 'interface' => 'M.2 NVMe', 'speed' => 4150 ] ),
				$make_component( 'storage_2', 'Samsung 990 PRO 1TB NVMe SSD', 9900, [ '1TB', 'NVMe Gen4', '7450MB/s' ], [ 'capacity' => 1, 'type' => 'NVMe SSD', 'interface' => 'M.2 NVMe', 'speed' => 7450 ] ),
				$make_component( 'storage_3', 'WD Black SN850X 2TB NVMe SSD', 13900, [ '2TB', 'NVMe Gen4', '7300MB/s' ], [ 'capacity' => 2, 'type' => 'NVMe SSD', 'interface' => 'M.2 NVMe', 'speed' => 7300 ] ),
				$make_component( 'storage_4', 'Crucial P3 Plus 2TB NVMe SSD', 11900, [ '2TB', 'NVMe Gen4', '5000MB/s' ], [ 'capacity' => 2, 'type' => 'NVMe SSD', 'interface' => 'M.2 NVMe', 'speed' => 5000 ] ),
				$make_component( 'storage_5', 'Seagate BarraCuda 2TB 3.5-inch HDD', 5900, [ '2TB', 'SATA HDD', '7200 RPM' ], [ 'capacity' => 2, 'type' => 'HDD', 'interface' => 'SATA', 'speed' => 0 ] ),
				$make_component( 'storage_6', 'Seagate BarraCuda 4TB 3.5-inch HDD', 9900, [ '4TB', 'SATA HDD', '5400 RPM' ], [ 'capacity' => 4, 'type' => 'HDD', 'interface' => 'SATA', 'speed' => 0 ] ),
			],
		],
		'psu' => [
			'category'   => 'Power Supply (PSU)',
			'filter_key' => 'wattage',
			'components' => [
				$make_component( 'psu_1', 'Corsair CX650 650W 80+ Bronze', 5900, [ '650W', '80+ Bronze', 'ATX' ], [ 'wattage' => 650, 'efficiency' => '80+ Bronze' ] ),
				$make_component( 'psu_2', 'Corsair RM750e 750W 80+ Gold', 8900, [ '750W', '80+ Gold', 'Fully Modular' ], [ 'wattage' => 750, 'efficiency' => '80+ Gold' ] ),
				$make_component( 'psu_3', 'Cooler Master MWE 850 Gold V2 850W', 10500, [ '850W', '80+ Gold', 'Fully Modular' ], [ 'wattage' => 850, 'efficiency' => '80+ Gold' ] ),
				$make_component( 'psu_4', 'Corsair RM850e 850W 80+ Gold ATX 3.1', 12900, [ '850W', '80+ Gold', 'ATX 3.1 / PCIe 5.1' ], [ 'wattage' => 850, 'efficiency' => '80+ Gold' ] ),
				$make_component( 'psu_5', 'MSI MAG A1000GL PCIE5 1000W 80+ Gold', 15900, [ '1000W', '80+ Gold', 'ATX 3.0 / PCIe 5.0' ], [ 'wattage' => 1000, 'efficiency' => '80+ Gold' ] ),
			],
		],
		'case' => [
			'category'   => 'PC Case',
			'components' => [
				$make_component( 'case_1', 'Ant Esports Crystal ARC Pro ATX Mid Tower', 9380, [ 'Latest 2026 listing', 'ATX / Micro-ATX / Mini-ITX', 'Tempered glass', 'Mesh airflow' ], [ 'form_factor' => 'ATX', 'supported_form_factors' => [ 'ATX', 'mATX', 'Mini-ITX' ], 'size' => 'Mid Tower' ] ),
			],
		],
		'cooling' => [
			'category'   => 'CPU Cooler',
			'components' => [
				$make_component( 'cooling_1', 'DeepCool AG400', 2200, [ 'Best-selling air cooler', '120mm single tower', 'LGA1700 / AM5' ], [ 'type' => 'Air', 'socket_support' => [ 'LGA1700', 'AM5' ] ] ),
				$make_component( 'cooling_2', 'Thermalright Peerless Assassin 120 SE', 4500, [ 'Popular dual-tower air cooler', 'Dual 120mm fans', 'LGA1700 / AM5' ], [ 'type' => 'Air', 'socket_support' => [ 'LGA1700', 'AM5' ] ] ),
				$make_component( 'cooling_3', 'DeepCool AK620', 6500, [ 'Dual-tower air cooler', 'Dual 120mm fans', 'LGA1700 / AM5' ], [ 'type' => 'Air', 'socket_support' => [ 'LGA1700', 'AM5' ] ] ),
				$make_component( 'cooling_4', 'Arctic Liquid Freezer III Pro 360', 14900, [ 'Latest 360mm AIO', 'Three 120mm fans', 'LGA1700 / AM5' ], [ 'type' => 'Liquid', 'socket_support' => [ 'LGA1700', 'AM5' ] ] ),
			],
		],
	],
];
