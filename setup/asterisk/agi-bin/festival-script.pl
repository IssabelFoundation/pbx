#!/usr/bin/perl

use Asterisk::AGI;
use File::Basename;
require Data::UUID;

$AGI = new Asterisk::AGI;

my $ug = new Data::UUID;

my $timestamp = gmtime; 
my %input = $AGI->ReadParse();
my ($text)=@ARGV;
my $hash = $ug->create_str;
my $sounddir = "/var/lib/asterisk/sounds/tts";
my $wavefile = "$sounddir/"."tts-$hash.wav";
my $t2wp= "/usr/bin/";
  
unless (-f $wavefile) {
        open(fileOUT, ">$sounddir"."/say-text-$hash.txt");
        print fileOUT "$text";
        close(fileOUT);
        my $execf=$t2wp."flite $sounddir/say-text-$hash.txt -F 8000 -o $wavefile";
        system($execf);
        unlink($sounddir."/say-text-$hash.txt");
}

$AGI->stream_file('tts/'.basename($wavefile,".wav")); 

