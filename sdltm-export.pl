use strict;
use warnings;
use utf8;
use DBI;
use Win32::OLE qw( in with CP_UTF8 );
use Win32::OLE::Const 'Microsoft Excel';
use File::Basename;
use Encode;
use File::Find::Rule;

my $dir = shift;
$dir =~ s{^"}{};
$dir =~ s{"$}{};
my @sdltm = File::Find::Rule->file->name(qr/\.sdltm$/i)->in($dir);

Win32::OLE->Option( CP => CP_UTF8 );
my $Excel = Win32::OLE->new( 'Excel.Application', 'Quit' );
$Excel->{'Visible'}       = 0;
$Excel->{'DisplayAlarts'} = 0;

foreach my $sdltm ( @sdltm ){
	print $sdltm . "\n";

	# Move to the directory because the DBI module does not support Japanese.
	my ($name, $dir) = fileparse($sdltm);
	chdir $dir;

	# Temporarily rename the file.
	my $tmp_name = int(rand(20000000));
	$tmp_name .= '.sdltm';
	if (-e $tmp_name) {
    	warn "$tmp_name already exists.\n";
	} else {
    	rename $name, $tmp_name or warn "Cannot rename files: $!";
	}

	my $book  = $Excel->Workbooks->add();
	my $sheet = $book->Worksheets(1);
	
	$sheet->Range("A1")->{'Value'} = 'Source';
	$sheet->Range("B1")->{'Value'} = 'Target';
	
	# Connect to DB.
	my $dbh = DBI->connect("dbi:SQLite:dbname=$tmp_name");
	
	# Source and Target only.
	my $select = "select source_segment, target_segment from translation_units;"; 
	
	my $sth = $dbh->prepare($select);
	$sth->execute;
	
	my $count = 2;
	while(my ($source, $target) = $sth->fetchrow()){
		$source = &seikei($source);
		$target = &seikei($target);
		$sheet->Range("A$count")->{'Value'} = $source;
		$sheet->Range("B$count")->{'Value'} = $target;
		$count++;
	}
	
	$dbh->disconnect;

	# Undo the file name.
	if (-e $name){
		warn "$name already exists.\n";
	} else {
		rename $tmp_name, $name or warn "Cannot rename files: $!";
	}

	# Save to Excel.
	( my $xlsx = $sdltm ) =~ s{\.sdltm}{.xlsx}i;
	$book->SaveAs( $xlsx );
	$book->Close;
}

$Excel->quit();

print "\nDone!\n";

sub seikei {
	my $s = shift;

	$s = decode('utf8', $s); 
	my $str;

	# Get the text surrounded by Value tags.
	while ( $s =~ s{<Value>(.+?)</Value>}{$1}s ) {
		$str .= $1;
	}

	return $str;
}
