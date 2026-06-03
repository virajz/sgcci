<?php

declare(strict_types=1);

namespace App\Livewire\Exhibitions;

use App\Jobs\SendSmsMessage;
use App\Jobs\SendWhatsAppCampaign;
use App\Models\Exhibition;
use App\Models\ExhibitionVisitor;
use App\Models\Segment;
use App\Models\SubSegment;
use App\VisitorRegistrationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.front')]
class VisitorsRegistration extends Component
{
    #[Locked]
    public int $exhibitionId;

    #[Locked]
    public ?string $source = null;

    public int $currentStep = 1;

    public string $phoneNumber = '';

    public string $name = '';

    public string $companyName = '';

    public string $designation = '';

    public string $state = '';

    public string $city = '';

    public string $email = '';

    public ?int $segmentId = null;

    public ?int $subSegmentId = null;

    /**
     * @var array<int, array{name: string, phone_number: string}>
     */
    public array $additionalPersons = [];

    /**
     * @return array<string, array<string>>
     */
    public static function getStateCityMap(): array
    {
        return [
            'Andaman and Nicobar Islands' => ['Port Blair'],
            'Andhra Pradesh' => ['Visakhapatnam', 'Vijayawada', 'Guntur', 'Nellore', 'Kurnool', 'Rajahmundry', 'Kakinada', 'Tirupati', 'Anantapur', 'Kadapa', 'Vizianagaram', 'Eluru', 'Ongole', 'Nandyal', 'Machilipatnam', 'Adoni', 'Tenali', 'Chittoor', 'Hindupur', 'Proddatur', 'Bhimavaram', 'Madanapalle', 'Guntakal', 'Dharmavaram', 'Gudivada', 'Srikakulam', 'Narasaraopet', 'Rajampet', 'Tadpatri', 'Tadepalligudem', 'Chilakaluripet', 'Yemmiganur', 'Kadiri', 'Chirala', 'Anakapalle', 'Kavali', 'Palacole', 'Sullurpeta', 'Tanuku', 'Rayachoti', 'Srikalahasti', 'Bapatla', 'Naidupet', 'Nagari', 'Gudur', 'Vinukonda', 'Narasapuram', 'Nuzvid', 'Markapur', 'Ponnur', 'Kandukur', 'Bobbili', 'Rayadurg', 'Samalkot', 'Jaggaiahpet', 'Tuni', 'Amalapuram', 'Bheemunipatnam', 'Venkatagiri', 'Sattenapalle', 'Pithapuram', 'Palasa Kasibugga', 'Parvathipuram', 'Macherla', 'Gooty', 'Salur', 'Mandapeta', 'Jammalamadugu', 'Peddapuram', 'Punganur', 'Nidadavole', 'Repalle', 'Ramachandrapuram', 'Kovvur', 'Tiruvuru', 'Uravakonda', 'Narsipatnam', 'Yerraguntla', 'Pedana', 'Puttur', 'Renigunta', 'Rajam'],
            'Arunachal Pradesh' => ['Naharlagun', 'Pasighat'],
            'Assam' => ['Guwahati', 'Silchar', 'Dibrugarh', 'Nagaon', 'Tinsukia', 'Jorhat', 'Bongaigaon City', 'Dhubri', 'Diphu', 'North Lakhimpur', 'Tezpur', 'Karimganj', 'Sibsagar', 'Goalpara', 'Barpeta', 'Lanka', 'Lumding', 'Mankachar', 'Nalbari', 'Rangia', 'Margherita', 'Mangaldoi', 'Silapathar', 'Mariani', 'Marigaon', 'Dispur'],
            'Bihar' => ['Patna', 'Gaya', 'Bhagalpur', 'Muzaffarpur', 'Darbhanga', 'Arrah', 'Begusarai', 'Chhapra', 'Katihar', 'Munger', 'Purnia', 'Saharsa', 'Sasaram', 'Hajipur', 'Dehri-on-Sone', 'Bettiah', 'Motihari', 'Bagaha', 'Siwan', 'Kishanganj', 'Jamalpur', 'Buxar', 'Jehanabad', 'Aurangabad', 'Lakhisarai', 'Nawada', 'Jamui', 'Sitamarhi', 'Araria', 'Gopalganj', 'Madhubani', 'Masaurhi', 'Samastipur', 'Mokameh', 'Supaul', 'Dumraon', 'Arwal', 'Forbesganj', 'Narkatiaganj', 'Naugachhia', 'Madhepura', 'Sheikhpura', 'Sultanganj', 'Raxaul Bazar', 'Ramnagar', 'Mahnar Bazar', 'Warisaliganj', 'Revelganj', 'Rajgir', 'Sonepur', 'Sherghati', 'Sugauli', 'Makhdumpur', 'Maner', 'Rosera', 'Nokha', 'Piro', 'Rafiganj', 'Marhaura', 'Mirganj', 'Lalganj', 'Murliganj', 'Motipur', 'Manihari', 'Sheohar', 'Maharajganj', 'Silao', 'Barh', 'Asarganj'],
            'Chandigarh' => ['Chandigarh'],
            'Chhattisgarh' => ['Raipur', 'Bhilai Nagar', 'Korba', 'Bilaspur', 'Durg', 'Rajnandgaon', 'Jagdalpur', 'Raigarh', 'Ambikapur', 'Mahasamund', 'Dhamtari', 'Chirmiri', 'Bhatapara', 'Dalli-Rajhara', 'Naila Janjgir', 'Tilda Newra', 'Mungeli', 'Manendragarh', 'Sakti'],
            'Dadra and Nagar Haveli' => ['Silvassa'],
            'Delhi' => ['Delhi', 'New Delhi'],
            'Goa' => ['Marmagao', 'Panaji', 'Margao', 'Mapusa'],
            'Gujarat' => ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar', 'Jamnagar', 'Nadiad', 'Porbandar', 'Anand', 'Morvi', 'Mahesana', 'Bharuch', 'Vapi', 'Navsari', 'Veraval', 'Bhuj', 'Godhra', 'Palanpur', 'Valsad', 'Patan', 'Deesa', 'Amreli', 'Anjar', 'Dhoraji', 'Khambhat', 'Mahuva', 'Keshod', 'Wadhwan', 'Ankleshwar', 'Savarkundla', 'Kadi', 'Visnagar', 'Upleta', 'Una', 'Sidhpur', 'Unjha', 'Mangrol', 'Viramgam', 'Modasa', 'Palitana', 'Petlad', 'Kapadvanj', 'Sihor', 'Wankaner', 'Limbdi', 'Mandvi', 'Thangadh', 'Vyara', 'Padra', 'Lunawada', 'Rajpipla', 'Umreth', 'Sanand', 'Rajula', 'Radhanpur', 'Mahemdabad', 'Ranavav', 'Tharad', 'Mansa', 'Umbergaon', 'Talaja', 'Vadnagar', 'Manavadar', 'Salaya', 'Vijapur', 'Pardi', 'Rapar', 'Songadh', 'Lathi', 'Adalaj', 'Gandhinagar'],
            'Haryana' => ['Faridabad', 'Gurgaon', 'Hisar', 'Rohtak', 'Panipat', 'Karnal', 'Sonipat', 'Yamunanagar', 'Panchkula', 'Bhiwani', 'Bahadurgarh', 'Jind', 'Sirsa', 'Thanesar', 'Kaithal', 'Palwal', 'Rewari', 'Hansi', 'Narnaul', 'Fatehabad', 'Gohana', 'Tohana', 'Narwana', 'Mandi Dabwali', 'Charkhi Dadri', 'Shahbad', 'Pehowa', 'Samalkha', 'Pinjore', 'Ladwa', 'Sohna', 'Safidon', 'Taraori', 'Mahendragarh', 'Ratia', 'Rania', 'Sarsod'],
            'Himachal Pradesh' => ['Shimla', 'Mandi', 'Solan', 'Nahan', 'Sundarnagar', 'Palampur', 'Kullu', 'Manali'],
            'Jammu and Kashmir' => ['Srinagar', 'Jammu', 'Baramula', 'Anantnag', 'Sopore', 'Rajauri', 'Punch', 'Udhampur'],
            'Jharkhand' => ['Dhanbad', 'Ranchi', 'Jamshedpur', 'Bokaro Steel City', 'Deoghar', 'Phusro', 'Adityapur', 'Hazaribag', 'Giridih', 'Ramgarh', 'Jhumri Tilaiya', 'Saunda', 'Sahibganj', 'Medininagar', 'Chaibasa', 'Chatra', 'Gumia', 'Dumka', 'Madhupur', 'Chirkunda', 'Pakaur', 'Simdega', 'Musabani', 'Mihijam', 'Patratu', 'Lohardaga', 'Tenu dam-cum-Kathhara'],
            'Karnataka' => ['Bengaluru', 'Hubli-Dharwad', 'Belagavi', 'Mangaluru', 'Davanagere', 'Ballari', 'Mysore', 'Tumkur', 'Shivamogga', 'Raayachuru', 'Kolar', 'Mandya', 'Udupi', 'Chikkamagaluru', 'Karwar', 'Ranebennuru', 'Ranibennur', 'Ramanagaram', 'Gokak', 'Yadgir', 'Rabkavi Banhatti', 'Shahabad', 'Sirsi', 'Sindhnur', 'Tiptur', 'Arsikere', 'Nanjangud', 'Sagara', 'Sira', 'Puttur', 'Athni', 'Mulbagal', 'Surapura', 'Siruguppa', 'Mudhol', 'Sidlaghatta', 'Shahpur', 'Saundatti-Yellamma', 'Wadi', 'Manvi', 'Nelamangala', 'Lakshmeshwar', 'Ramdurg', 'Nargund', 'Tarikere', 'Malavalli', 'Savanur', 'Lingsugur', 'Vijayapura', 'Sankeshwara', 'Madikeri', 'Talikota', 'Sedam', 'Shikaripur', 'Mahalingapura', 'Mudalagi', 'Muddebihal', 'Pavagada', 'Malur', 'Sindhagi', 'Sanduru', 'Afzalpur', 'Maddur', 'Madhugiri', 'Tekkalakote', 'Terdal', 'Mudabidri', 'Magadi', 'Navalgund', 'Shiggaon', 'Shrirangapattana', 'Sindagi', 'Sakaleshapura', 'Srinivaspur', 'Ron', 'Mundargi', 'Sadalagi', 'Piriyapatna', 'Adyar'],
            'Kerala' => ['Thiruvananthapuram', 'Kochi', 'Kozhikode', 'Kollam', 'Thrissur', 'Palakkad', 'Alappuzha', 'Malappuram', 'Ponnani', 'Vatakara', 'Kanhangad', 'Taliparamba', 'Koyilandy', 'Neyyattinkara', 'Kayamkulam', 'Nedumangad', 'Kannur', 'Tirur', 'Kottayam', 'Kasaragod', 'Kunnamkulam', 'Ottappalam', 'Thiruvalla', 'Thodupuzha', 'Chalakudy', 'Changanassery', 'Punalur', 'Nilambur', 'Cherthala', 'Perinthalmanna', 'Mattannur', 'Shoranur', 'Varkala', 'Paravoor', 'Pathanamthitta', 'Peringathur', 'Attingal', 'Kodungallur', 'Pappinisseri', 'Chittur-Thathamangalam', 'Muvattupuzha', 'Adoor', 'Mavelikkara', 'Mavoor', 'Perumbavoor', 'Vaikom', 'Palai', 'Panniyannur', 'Guruvayoor', 'Puthuppally', 'Panamattom'],
            'Ladakh' => ['Leh', 'Kargil'],
            'Lakshadweep' => ['Kavaratti'],
            'Madhya Pradesh' => ['Indore', 'Bhopal', 'Jabalpur', 'Gwalior', 'Ujjain', 'Sagar', 'Ratlam', 'Satna', 'Katni', 'Morena', 'Singrauli', 'Rewa', 'Vidisha', 'Ganjbasoda', 'Shivpuri', 'Mandsaur', 'Neemuch', 'Nagda', 'Itarsi', 'Sarni', 'Sehore', 'Seoni', 'Balaghat', 'Ashok Nagar', 'Tikamgarh', 'Shahdol', 'Pithampur', 'Alirajpur', 'Mandla', 'Sheopur', 'Shajapur', 'Panna', 'Sendhwa', 'Sidhi', 'Pipariya', 'Shujalpur', 'Sironj', 'Pandhurna', 'Nowgong', 'Mandideep', 'Sihora', 'Raisen', 'Lahar', 'Maihar', 'Sanawad', 'Sabalgarh', 'Umaria', 'Porsa', 'Narsinghgarh', 'Malaj Khand', 'Sarangpur', 'Mundi', 'Nepanagar', 'Mahidpur', 'Seoni-Malwa', 'Rehli', 'Manawar', 'Rahatgarh', 'Panagar', 'Tarana', 'Sausar', 'Rajgarh', 'Niwari', 'Mauganj', 'Manasa', 'Nainpur', 'Prithvipur', 'Sohagpur', 'Shamgarh', 'Maharajpur', 'Multai', 'Pali', 'Pachore', 'Rau', 'Vijaypur'],
            'Maharashtra' => ['Mumbai', 'Pune', 'Nagpur', 'Thane', 'Nashik', 'Kalyan-Dombivali', 'Vasai-Virar', 'Solapur', 'Mira-Bhayandar', 'Bhiwandi', 'Amravati', 'Nanded-Waghala', 'Sangli', 'Malegaon', 'Akola', 'Latur', 'Dhule', 'Ahmednagar', 'Ichalkaranji', 'Parbhani', 'Panvel', 'Yavatmal', 'Achalpur', 'Osmanabad', 'Nandurbar', 'Satara', 'Wardha', 'Udgir', 'Aurangabad', 'Amalner', 'Akot', 'Pandharpur', 'Shrirampur', 'Parli', 'Washim', 'Ambejogai', 'Manmad', 'Ratnagiri', 'Uran Islampur', 'Pusad', 'Sangamner', 'Shirpur-Warwade', 'Malkapur', 'Wani', 'Lonavla', 'Talegaon Dabhade', 'Anjangaon', 'Umred', 'Palghar', 'Shegaon', 'Ozar', 'Phaltan', 'Yevla', 'Shahade', 'Vita', 'Umarkhed', 'Warora', 'Pachora', 'Tumsar', 'Manjlegaon', 'Sillod', 'Arvi', 'Nandura', 'Vaijapur', 'Sailu', 'Murtijapur', 'Tasgaon', 'Mehkar', 'Yawal', 'Pulgaon', 'Nilanga', 'Wai', 'Umarga', 'Paithan', 'Rahuri', 'Nawapur', 'Tuljapur', 'Morshi', 'Purna', 'Satana', 'Pathri', 'Sinnar', 'Uran', 'Pen', 'Karjat', 'Manwath', 'Partur', 'Sangole', 'Mangrulpir', 'Risod', 'Shirur', 'Savner', 'Sasvad', 'Pandharkaoda', 'Talode', 'Shrigonda', 'Shirdi', 'Raver', 'Mukhed', 'Rajura', 'Tirora', 'Mahad', 'Lonar', 'Sawantwadi', 'Pathardi', 'Pauni', 'Ramtek', 'Mul', 'Mangalvedhe', 'Narkhed', 'Patur', 'Mhaswad', 'Loha', 'Nandgaon', 'Warud'],
            'Manipur' => ['Imphal', 'Thoubal', 'Lilong', 'Mayang Imphal'],
            'Meghalaya' => ['Shillong', 'Tura', 'Nongstoin'],
            'Mizoram' => ['Aizawl', 'Lunglei', 'Saiha'],
            'Nagaland' => ['Dimapur', 'Kohima', 'Zunheboto', 'Tuensang', 'Wokha', 'Mokokchung'],
            'Odisha' => ['Bhubaneswar', 'Cuttack', 'Raurkela', 'Brahmapur', 'Sambalpur', 'Puri', 'Baleshwar Town', 'Baripada Town', 'Bhadrak', 'Balangir', 'Jharsuguda', 'Bargarh', 'Paradip', 'Bhawanipatna', 'Dhenkanal', 'Barbil', 'Kendujhar', 'Sunabeda', 'Rayagada', 'Jatani', 'Byasanagar', 'Kendrapara', 'Rajagangapur', 'Parlakhemundi', 'Talcher', 'Sundargarh', 'Phulabani', 'Pattamundai', 'Titlagarh', 'Nabarangapur', 'Soro', 'Malkangiri', 'Rairangpur', 'Tarbha'],
            'Puducherry' => ['Pondicherry', 'Karaikal', 'Yanam', 'Mahe'],
            'Punjab' => ['Ludhiana', 'Patiala', 'Amritsar', 'Jalandhar', 'Bathinda', 'Pathankot', 'Hoshiarpur', 'Batala', 'Moga', 'Malerkotla', 'Khanna', 'Mohali', 'Barnala', 'Firozpur', 'Phagwara', 'Kapurthala', 'Zirakpur', 'Kot Kapura', 'Faridkot', 'Muktsar', 'Rajpura', 'Sangrur', 'Fazilka', 'Gurdaspur', 'Kharar', 'Gobindgarh', 'Mansa', 'Malout', 'Nabha', 'Tarn Taran', 'Jagraon', 'Sunam', 'Dhuri', 'Firozpur Cantt.', 'Rupnagar', 'Samana', 'Nawanshahr', 'Rampura Phul', 'Nangal', 'Nakodar', 'Zira', 'Patti', 'Raikot', 'Longowal', 'Morinda', 'Phillaur', 'Pattran', 'Qadian', 'Sujanpur', 'Mukerian', 'Talwara'],
            'Rajasthan' => ['Jaipur', 'Jodhpur', 'Bikaner', 'Udaipur', 'Ajmer', 'Bhilwara', 'Alwar', 'Bharatpur', 'Pali', 'Barmer', 'Sikar', 'Tonk', 'Sadulpur', 'Sawai Madhopur', 'Nagaur', 'Makrana', 'Sujangarh', 'Sardarshahar', 'Ladnu', 'Ratangarh', 'Nokha', 'Nimbahera', 'Suratgarh', 'Rajsamand', 'Lachhmangarh', 'Nasirabad', 'Nohar', 'Phalodi', 'Nathdwara', 'Pilani', 'Merta City', 'Sojat', 'Neem-Ka-Thana', 'Sirohi', 'Pratapgarh', 'Rawatbhata', 'Sangaria', 'Lalsot', 'Pilibanga', 'Pipar City', 'Taranagar', 'Sumerpur', 'Sagwara', 'Ramganj Mandi', 'Lakheri', 'Udaipurwati', 'Losal', 'Sri Madhopur', 'Rawatsar', 'Shahpura', 'Raisinghnagar', 'Malpura', 'Nadbai', 'Sanchore', 'Nagar', 'Sheoganj', 'Sadri', 'Todaraisingh', 'Todabhim', 'Reengus', 'Rajaldesar', 'Sadulshahar', 'Sambhar', 'Prantij', 'Mount Abu', 'Mangrol', 'Phulera', 'Mandawa', 'Pindwara', 'Mandalgarh', 'Takhatgarh', 'Kota'],
            'Sikkim' => ['Gangtok', 'Namchi'],
            'Tamil Nadu' => ['Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem', 'Tirunelveli', 'Tiruppur', 'Ranipet', 'Nagercoil', 'Thanjavur', 'Vellore', 'Kancheepuram', 'Erode', 'Tiruvannamalai', 'Pollachi', 'Rajapalayam', 'Sivakasi', 'Pudukkottai', 'Nagapattinam', 'Viluppuram', 'Tiruchengode', 'Vaniyambadi', 'Theni Allinagaram', 'Udhagamandalam', 'Aruppukkottai', 'Paramakudi', 'Arakkonam', 'Virudhachalam', 'Srivilliputhur', 'Tindivanam', 'Virudhunagar', 'Karur', 'Valparai', 'Sankarankovil', 'Tenkasi', 'Palani', 'Pattukkottai', 'Tirupathur', 'Ramanathapuram', 'Udumalaipettai', 'Gobichettipalayam', 'Thiruvarur', 'Thiruvallur', 'Panruti', 'Namakkal', 'Thirumangalam', 'Vikramasingapuram', 'Nellikuppam', 'Rasipuram', 'Tiruttani', 'Nandivaram-Guduvancheri', 'Periyakulam', 'Pernampattu', 'Vellakoil', 'Sivaganga', 'Vadalur', 'Rameshwaram', 'Perambalur', 'Usilampatti', 'Vedaranyam', 'Sathyamangalam', 'Puliyankudi', 'Nanjikottai', 'Thuraiyur', 'Sirkali', 'Tiruchendur', 'Sattur', 'Vandavasi', 'Tharangambadi', 'Tirukkoyilur', 'Oddanchatram', 'Palladam', 'Vadakkuvalliyur', 'Tirukalukundram', 'Uthamapalayam', 'Surandai', 'Sankari', 'Shenkottai', 'Vadipatti', 'Sholingur', 'Manachanallur', 'Polur', 'Panagudi', 'Uthiramerur', 'Thiruthuraipoondi', 'Pallapatti', 'Ponneri', 'Lalgudi', 'Natham', 'Unnamalaikadai', 'Tharangambadi', 'Tittakudi', 'Sholavandan', 'Namagiripettai', 'Peravurani', 'Parangipettai', 'Pallikonda', 'Sivagiri', 'Punjaipugalur', 'Padmanabhapuram', 'Thirupuvanam'],
            'Telangana' => ['Hyderabad', 'Warangal', 'Nizamabad', 'Karimnagar', 'Ramagundam', 'Khammam', 'Mahbubnagar', 'Mancherial', 'Adilabad', 'Suryapet', 'Jagtial', 'Miryalaguda', 'Nirmal', 'Kamareddy', 'Kothagudem', 'Bodhan', 'Palwancha', 'Mandamarri', 'Koratla', 'Sircilla', 'Tandur', 'Siddipet', 'Wanaparthy', 'Kagaznagar', 'Gadwal', 'Sangareddy', 'Bellampalle', 'Bhongir', 'Vikarabad', 'Jangaon', 'Bhadrachalam', 'Bhainsa', 'Farooqnagar', 'Medak', 'Narayanpet', 'Sadasivpet', 'Yellandu', 'Manuguru', 'Kyathampalle', 'Nagarkurnool'],
            'Tripura' => ['Agartala', 'Udaipur', 'Dharmanagar', 'Pratapgarh', 'Kailasahar', 'Belonia', 'Khowai'],
            'Uttar Pradesh' => ['Lucknow', 'Kanpur', 'Firozabad', 'Agra', 'Meerut', 'Varanasi', 'Allahabad', 'Amroha', 'Moradabad', 'Aligarh', 'Saharanpur', 'Noida', 'Loni', 'Jhansi', 'Shahjahanpur', 'Rampur', 'Modinagar', 'Hapur', 'Etawah', 'Sambhal', 'Orai', 'Bahraich', 'Unnao', 'Rae Bareli', 'Lakhimpur', 'Sitapur', 'Lalitpur', 'Pilibhit', 'Chandausi', 'Hardoi', 'Azamgarh', 'Khair', 'Sultanpur', 'Tanda', 'Nagina', 'Shamli', 'Najibabad', 'Shikohabad', 'Sikandrabad', 'Pilkhuwa', 'Renukoot', 'Vrindavan', 'Ujhani', 'Laharpur', 'Tilhar', 'Sahaswan', 'Rath', 'Sherkot', 'Kalpi', 'Tundla', 'Sandila', 'Nanpara', 'Sardhana', 'Nehtaur', 'Seohara', 'Padrauna', 'Mathura', 'Thakurdwara', 'Nawabganj', 'Siana', 'Noorpur', 'Sikandra Rao', 'Puranpur', 'Rudauli', 'Thana Bhawan', 'Palia Kalan', 'Zaidpur', 'Nautanwa', 'Zamania', 'Naugawan Sadat', 'Fatehpur Sikri', 'Robertsganj', 'Utraula', 'Sadabad', 'Rasra', 'Lar', 'Sirsaganj', 'Pihani', 'Rudrapur', 'Soron', 'Samdhan', 'Sahjanwa', 'Rampur Maniharan', 'Sumerpur', 'Shahganj', 'Tulsipur', 'Tirwaganj', 'Powayan', 'Sandi', 'Achhnera', 'Naraura', 'Nakur', 'Sahaspur', 'Safipur', 'Reoti', 'Sikanderpur', 'Saidpur', 'Sirsi', 'Purwa', 'Parasi', 'Lalganj', 'Phulpur', 'Shishgarh', 'Sahawar', 'Samthar', 'Pukhrayan', 'Obra', 'Niwai', 'Mirzapur', 'Ghaziabad', 'Bareilly'],
            'Uttarakhand' => ['Dehradun', 'Hardwar', 'Haldwani', 'Srinagar', 'Kashipur', 'Roorkee', 'Rudrapur', 'Rishikesh', 'Ramnagar', 'Pithoragarh', 'Manglaur', 'Nainital', 'Mussoorie', 'Tehri', 'Pauri', 'Nagla', 'Sitarganj', 'Bageshwar'],
            'West Bengal' => ['Kolkata', 'Siliguri', 'Asansol', 'Raghunathganj', 'Kharagpur', 'Naihati', 'English Bazar', 'Baharampur', 'Hugli-Chinsurah', 'Raiganj', 'Jalpaiguri', 'Santipur', 'Balurghat', 'Medinipur', 'Habra', 'Ranaghat', 'Bankura', 'Nabadwip', 'Darjiling', 'Purulia', 'Arambagh', 'Tamluk', 'Suri', 'Jhargram', 'Gangarampur', 'Rampurhat', 'Kalimpong', 'Sainthia', 'Taki', 'Murshidabad', 'Memari', 'Tarakeswar', 'Sonamukhi', 'Mainaguri', 'Malda', 'Panchla', 'Raghunathpur', 'Mathabhanga', 'Monoharpur', 'Srirampore', 'Adra'],
        ];
    }

    public function mount(Exhibition $exhibition): void
    {
        if (! empty($exhibition->redirect_url)) {
            $this->redirect($exhibition->redirect_url);

            return;
        }

        $this->exhibitionId = $exhibition->id;
        $this->source = request()->query('source');
    }

    public function updatedState(): void
    {
        $this->city = '';
    }

    public function updatedSegmentId(): void
    {
        $this->subSegmentId = null;
    }

    /**
     * @return Collection<int, Segment>
     */
    #[Computed]
    public function segments(): Collection
    {
        return Segment::active()->ordered()->get(['id', 'name']);
    }

    /**
     * @return Collection<int, SubSegment>
     */
    #[Computed]
    public function subSegments(): Collection
    {
        if (! $this->segmentId) {
            return collect();
        }

        return SubSegment::active()
            ->where('segment_id', $this->segmentId)
            ->ordered()
            ->get(['id', 'segment_id', 'name']);
    }

    /**
     * @return array<string>
     */
    #[Computed]
    public function cities(): array
    {
        $map = static::getStateCityMap();

        return $map[$this->state] ?? [];
    }

    #[Computed]
    public function totalPersons(): int
    {
        return 1 + count($this->additionalPersons);
    }

    #[Computed]
    public function totalAmount(): ?float
    {
        $exhibition = Exhibition::findOrFail($this->exhibitionId);

        if (! $exhibition->isPaidEntry()) {
            return null;
        }

        return (float) $exhibition->entry_amount * $this->totalPersons;
    }

    public function goToStep(int $step): void
    {
        if ($step === 2) {
            $this->validateStep1();
        }

        $this->currentStep = $step;
    }

    public function nextStep(): void
    {
        $this->validateStep1();
        $this->currentStep = 2;
    }

    public function previousStep(): void
    {
        $this->currentStep = 1;
    }

    public function addPerson(): void
    {
        $this->additionalPersons[] = ['name' => '', 'phone_number' => ''];
    }

    public function removePerson(int $index): void
    {
        array_splice($this->additionalPersons, $index, 1);
        $this->additionalPersons = array_values($this->additionalPersons);
    }

    public function register(): void
    {
        $this->validateStep1();
        $this->validateStep2();

        $exhibition = Exhibition::findOrFail($this->exhibitionId);

        $additionalPersonsData = array_values(
            array_filter(
                $this->additionalPersons,
                fn (array $person) => ! empty(trim($person['name']))
            )
        );

        $totalAmount = $exhibition->isPaidEntry()
            ? (float) $exhibition->entry_amount * (1 + count($additionalPersonsData))
            : null;

        $newStatus = $exhibition->isPaidEntry()
            ? VisitorRegistrationStatus::PaymentPending
            : VisitorRegistrationStatus::Confirmed;

        // Reuse an existing incomplete registration (payment_pending or payment_failed)
        // to gracefully handle retries after an abandoned or failed payment.
        $existingIncomplete = ExhibitionVisitor::query()
            ->where('exhibition_id', $this->exhibitionId)
            ->where('phone_number', $this->phoneNumber)
            ->whereIn('status', [
                VisitorRegistrationStatus::PaymentPending->value,
                VisitorRegistrationStatus::PaymentFailed->value,
            ])
            ->whereNull('entered_at')
            ->first();

        $segmentName = $this->segmentId ? Segment::find($this->segmentId)?->name : null;
        $subSegmentName = $this->subSegmentId ? SubSegment::find($this->subSegmentId)?->name : null;

        if ($existingIncomplete) {
            $existingIncomplete->update([
                'name' => $this->name,
                'company_name' => $this->companyName ?: null,
                'designation' => $this->designation ?: null,
                'state' => $this->state,
                'city' => $this->city,
                'email' => $this->email ?: null,
                'business_segment' => $segmentName,
                'sub_business_segment' => $subSegmentName,
                'additional_persons' => ! empty($additionalPersonsData) ? $additionalPersonsData : null,
                'source' => $this->source,
                'payment_amount' => $totalAmount,
                'status' => $newStatus,
                'payment_initiated_at' => null,
                'payment_completed_at' => null,
                'payment_transaction_id' => null,
                'payment_tracking_id' => null,
                'payment_bank_ref_no' => null,
                'payment_status' => null,
                'payment_response' => null,
                'payment_notes' => null,
            ]);
            $visitor = $existingIncomplete->fresh();
        } else {
            $visitor = ExhibitionVisitor::create([
                'exhibition_id' => $this->exhibitionId,
                'phone_number' => $this->phoneNumber,
                'name' => $this->name,
                'company_name' => $this->companyName ?: null,
                'designation' => $this->designation ?: null,
                'state' => $this->state,
                'city' => $this->city,
                'email' => $this->email ?: null,
                'business_segment' => $segmentName,
                'sub_business_segment' => $subSegmentName,
                'additional_persons' => ! empty($additionalPersonsData) ? $additionalPersonsData : null,
                'source' => $this->source,
                'payment_amount' => $totalAmount,
                'status' => $newStatus,
            ]);
        }

        if ($exhibition->isPaidEntry()) {
            $this->redirect(
                route('visitor-payment.initiate', ['registrationCode' => $visitor->registration_code]),
                navigate: false
            );
        } else {
            // SMS disabled until DLT template is approved
            // Template ID 1707177157041193630 needs to be active in DLT portal
            // if (config('services.sms.enabled')) {
            //     $passLink = route('visitor.scan', [
            //         'exhibition' => $exhibition->slug,
            //         'registrationCode' => $visitor->registration_code,
            //     ]);
            //
            //     $exhibitionTitle = strlen($exhibition->title) > 20
            //         ? substr($exhibition->title, 0, 17) . '...'
            //         : $exhibition->title;
            //
            //     SendSmsMessage::dispatch(
            //         template: 'visitor_registration_confirmed',
            //         phoneCode: '',
            //         phoneNumber: $visitor->phone_number,
            //         variables: [
            //             explode(' ', trim($visitor->name))[0],  // {#var#} 1 - Name
            //             $passLink,                              // {#var#} 2 - Pass Link
            //         ],
            //         templateId: '1707177157041193630'
            //     );
            // }

            if (config('services.whatsapp.enabled')) {
                $this->sendWhatsAppNotification($visitor, $exhibition);
            }

            $this->redirect(
                route('visitors-registration.thank-you', [
                    'exhibition' => $exhibition,
                    'registrationCode' => $visitor->registration_code,
                ]),
                navigate: true
            );
        }
    }

    private function validateStep1(): void
    {
        $this->validate([
            'phoneNumber' => [
                'required',
                'string',
                'max:20',
                function (string $_attribute, mixed $value, \Closure $fail): void {
                    $alreadyRegistered = ExhibitionVisitor::query()
                        ->where('exhibition_id', $this->exhibitionId)
                        ->where('phone_number', trim((string) $value))
                        ->where('status', VisitorRegistrationStatus::Confirmed->value)
                        ->whereNull('entered_at')
                        ->exists();

                    if ($alreadyRegistered) {
                        $fail('This phone number is already registered for this exhibition.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'companyName' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'state' => ['required', 'string', 'in:'.implode(',', array_keys(static::getStateCityMap()))],
            'city' => ['required', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'segmentId' => ['nullable', 'integer', 'exists:segments,id'],
            'subSegmentId' => [
                'nullable',
                'integer',
                function (string $_attribute, mixed $value, \Closure $fail): void {
                    if (! $value) {
                        return;
                    }
                    $exists = SubSegment::where('id', $value)
                        ->where('segment_id', $this->segmentId)
                        ->exists();
                    if (! $exists) {
                        $fail('Please choose a sub-segment that belongs to the selected segment.');
                    }
                },
            ],
        ], [
            'phoneNumber.required' => 'Please enter your phone number.',
            'phoneNumber.unique' => 'This phone number is already registered for this exhibition.',
            'name.required' => 'Please enter your name.',
            'state.required' => 'Please select a state.',
            'city.required' => 'Please select a city.',
        ]);
    }

    private function validateStep2(): void
    {
        // Collect all phone numbers in the form (primary + additional) to check for duplicates within the form
        $allFormPhones = array_filter(
            array_map(fn (array $p) => trim($p['phone_number'] ?? ''), $this->additionalPersons)
        );

        $this->validate([
            'additionalPersons.*.name' => ['required', 'string', 'max:255'],
            'additionalPersons.*.phone_number' => [
                'required',
                'string',
                'max:20',
                function (string $_attribute, mixed $value, \Closure $fail) use ($allFormPhones): void {
                    $phone = trim((string) $value);

                    // Must not match the primary visitor's phone
                    if ($phone === trim($this->phoneNumber)) {
                        $fail('This phone number is already used as the primary visitor.');

                        return;
                    }

                    // Must not appear more than once across all additional persons
                    if (count(array_keys($allFormPhones, $phone)) > 1) {
                        $fail('Each additional person must have a unique phone number.');

                        return;
                    }

                    // Must not already be registered for this exhibition without having entered yet
                    $alreadyRegistered = ExhibitionVisitor::query()
                        ->where('exhibition_id', $this->exhibitionId)
                        ->where('status', VisitorRegistrationStatus::Confirmed->value)
                        ->whereNull('entered_at')
                        ->where(function (Builder $query) use ($phone): void {
                            $query->where('phone_number', $phone)
                                ->orWhereRaw(
                                    "EXISTS (SELECT 1 FROM jsonb_array_elements(additional_persons::jsonb) AS p WHERE p->>'phone_number' = ?)",
                                    [$phone]
                                );
                        })
                        ->exists();

                    if ($alreadyRegistered) {
                        $fail('This phone number is already registered for this exhibition.');
                    }
                },
            ],
        ], [
            'additionalPersons.*.name.required' => 'Please enter the name for each additional person.',
            'additionalPersons.*.name.max' => 'Each name may not exceed 255 characters.',
            'additionalPersons.*.phone_number.required' => 'Please enter the phone number for each additional person.',
            'additionalPersons.*.phone_number.max' => 'Each phone number may not exceed 20 characters.',
        ]);
    }

    private function sendWhatsAppNotification(ExhibitionVisitor $visitor, Exhibition $exhibition): void
    {
        // Format exhibition dates
        $exhibitionDates = $exhibition->start_date->format('d M Y').' to '.$exhibition->end_date->format('d M Y');

        // Send WhatsApp for primary visitor
        $primaryFirstName = explode(' ', trim($visitor->name))[0];
        $primaryImageUrl = config('app.url').'/'.$exhibition->slug.'/visitor-pass/'.$visitor->registration_code.'/image';

        SendWhatsAppCampaign::dispatch(
            campaignName: 'Paidregistration1',
            phoneCode: '',
            phoneNumber: $visitor->phone_number,
            templateParams: [
                $primaryFirstName,              // {{1}} - Name in title
                $exhibition->title,             // {{2}} - Exhibition
                $visitor->registration_code,    // {{3}} - Registration Code
                $primaryFirstName,              // {{4}} - Name in body
                $visitor->company_name ?: 'N/A', // {{5}} - Company
                $visitor->city,                 // {{6}} - City
                'Free Entry',                   // {{7}} - Amount
                'N/A',                          // {{8}} - Transaction ID
                $visitor->created_at->format('d-m-Y'), // {{9}} - Payment Date
                $exhibitionDates,               // {{10}} - Exhibition Dates
            ],
            paramsFallbackValue: [
                'FirstName' => 'Guest',
            ],
            media: [
                'url' => $primaryImageUrl,
                'filename' => 'visitor_pass_'.$visitor->registration_code,
            ]
        );

        // Send WhatsApp for each additional person
        if (! empty($visitor->additional_persons)) {
            foreach ($visitor->additional_persons as $index => $person) {
                $personFirstName = explode(' ', trim($person['name']))[0];
                $personImageUrl = config('app.url').'/'.$exhibition->slug.'/visitor-pass/'.$visitor->registration_code.'/image?personIndex='.$index;
                $personPhone = ! empty($person['phone_number']) ? $person['phone_number'] : $visitor->phone_number;

                SendWhatsAppCampaign::dispatch(
                    campaignName: 'Paidregistration1',
                    phoneCode: '',
                    phoneNumber: $personPhone,
                    templateParams: [
                        $personFirstName,               // {{1}} - Name in title
                        $exhibition->title,             // {{2}} - Exhibition
                        $visitor->registration_code,    // {{3}} - Registration Code
                        $personFirstName,               // {{4}} - Name in body
                        $visitor->company_name ?: 'N/A', // {{5}} - Company
                        $visitor->city,                 // {{6}} - City
                        'Free Entry',                   // {{7}} - Amount
                        'N/A',                          // {{8}} - Transaction ID
                        $visitor->created_at->format('d-m-Y'), // {{9}} - Payment Date
                        $exhibitionDates,               // {{10}} - Exhibition Dates
                    ],
                    paramsFallbackValue: [
                        'FirstName' => 'Guest',
                    ],
                    media: [
                        'url' => $personImageUrl,
                        'filename' => 'visitor_pass_'.$visitor->registration_code.'_person_'.($index + 1),
                    ]
                );
            }
        }
    }

    public function render()
    {
        $exhibition = Exhibition::findOrFail($this->exhibitionId);

        return view('livewire.exhibitions.visitors-registration', [
            'exhibition' => $exhibition,
            'states' => array_keys(static::getStateCityMap()),
        ]);
    }
}
