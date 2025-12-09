<?PHP
require '/home/moodle/composer/vendor/autoload.php';

use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;

$textToSpeechClient = new TextToSpeechClient([
    'credentials' => '/home/moodle/composer/google_cloud_credential.json',
]);

$input = new SynthesisInput();
$input->setText($inputtext);

$voice = new VoiceSelectionParams();
$voice->setLanguageCode('ko-KR');
$voice->setName('ko-KR-Wavenet-D');
 
$audioConfig = new AudioConfig();
$audioConfig->setAudioEncoding(AudioEncoding::MP3);
$audiofilename='audio_'.$contextid.'.mp3';
$resp = $textToSpeechClient->synthesizeSpeech($input, $voice, $audioConfig);
file_put_contents('../whiteboard/uploads/'.$audiofilename, $resp->getAudioContent());
echo '<audio autoplay>
<source src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/uploads/'.$audiofilename.'" type="audio/mpeg">
</audio>';
 
// ko-KR-Standard-A 여성   ko-KR-Standard-B 여성   ko-KR-Standard-C 남성   ko-KR-Standard-D 남성   ko-KR-Wavenet-A 여성   ko-KR-Wavenet-B 여성   ko-KR-Wavenet-C 남성   ko-KR-Wavenet-D 남성
 
?>