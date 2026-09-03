import urllib.request
import json

test_leads = [
    {
        'fullName': 'Rajesh Shah',
        'phoneNumber': '9876543210',
        'whatsappNumber': '9876543210',
        'websiteName': 'Apex Realty Ventures',
        'websiteUrl': 'apexrealty.com',
        'businessStage': 'Real Estate Developer / Broker',
        'toolUsed': 'Domain Authority Checker'
    },
    {
        'fullName': 'Dr. Ananya Patel',
        'phoneNumber': '9825012345',
        'whatsappNumber': '9825012345',
        'websiteName': 'Care Dental Clinic',
        'websiteUrl': 'caredental.in',
        'businessStage': 'Local Clinic / Hospital / Doctor',
        'toolUsed': 'Google Review QR Generator'
    },
    {
        'fullName': 'Vikram Malhotra',
        'phoneNumber': '9988776655',
        'whatsappNumber': '9988776655',
        'websiteName': 'Urban Vogue Store',
        'websiteUrl': 'urbanvogue.shop',
        'businessStage': 'E-Commerce / D2C Brand',
        'toolUsed': 'WhatsApp Link Generator'
    }
]

for l in test_leads:
    req = urllib.request.Request(
        'https://prmarketingventures.com/api/leads.php',
        data=json.dumps(l).encode('utf-8'),
        headers={'Content-Type': 'application/json', 'User-Agent': 'Mozilla/5.0'}
    )
    res = urllib.request.urlopen(req, timeout=10)
    print("Submitted for", l['fullName'], "->", res.read().decode('utf-8'))
