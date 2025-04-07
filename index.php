<?php

function processTransactions($transactions, $years, $interestRate = 0.05, $vatRate = 0.05) {
    $netBalance = 0;
    $categoryTotals = [
        'goods' => 0,
        'services' => 0,
        'investment' => 0
    ];
    

    foreach ($transactions as $transaction) {

        if (!isset($transaction['amount']) || !isset($transaction['type']) || !isset($transaction['category'])) {
            continue; 
        }
        
        $amount = floatval($transaction['amount']);
        $type = strtolower($transaction['type']);
        $category = strtolower($transaction['category']);
        
 
        if ($amount < 0) {
            continue;
        }
        

        if (!in_array($category, array_keys($categoryTotals))) {
            continue; 
        }
        

        $adjustedAmount = $amount;
        if ($category === 'goods' || $category === 'services') {
            if ($type === 'credit') {
                $adjustedAmount = $amount * (1 - $vatRate);
            } elseif ($type === 'debit') {
                $adjustedAmount = $amount * (1 + $vatRate);
            }
        }
        

        if ($type === 'credit') {
            $netBalance += $adjustedAmount;
            $categoryTotals[$category] += $adjustedAmount;
        } elseif ($type === 'debit') {
            $netBalance -= $adjustedAmount;
            $categoryTotals[$category] -= $adjustedAmount;
        }
    }
    

    $netBalance = round($netBalance, 2);
    foreach ($categoryTotals as $category => $total) {
        $categoryTotals[$category] = round($total, 2);
    }
    

    $finalBalance = $netBalance;
    if ($netBalance > 0 && $years > 0) {
        $finalBalance = $netBalance * pow(1 + $interestRate, $years);
        $finalBalance = round($finalBalance, 2);
    }
    
    return [
        'netBalance' => $netBalance,
        'finalBalance' => $finalBalance,
        'categoryBreakdown' => $categoryTotals
    ];
}


session_start();


$showResults = false;
$result = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process form data
    $transactions = [];
    $amounts = $_POST['amount'] ?? [];
    $types = $_POST['type'] ?? [];
    $categories = $_POST['category'] ?? [];
    

    for ($i = 0; $i < count($amounts); $i++) {
        if (isset($amounts[$i]) && isset($types[$i]) && isset($categories[$i])) {
            $transactions[] = [
                'amount' => floatval($amounts[$i]),
                'type' => $types[$i],
                'category' => $categories[$i]
            ];
        }
    }
    
    $years = floatval($_POST['years'] ?? 1);
    $interestRate = floatval($_POST['interestRate'] ?? 0.05);
    $vatRate = floatval($_POST['vatRate'] ?? 0.05);

    $result = processTransactions($transactions, $years, $interestRate, $vatRate);
    $showResults = true;
    

    $_SESSION['result'] = $result;
    $_SESSION['showResults'] = true;
    

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
} else if (isset($_SESSION['showResults']) && $_SESSION['showResults']) {

    $result = $_SESSION['result'];
    $showResults = true;
    
  
    $_SESSION['showResults'] = false;
    unset($_SESSION['result']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Transaction Processor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #2c3e50;
            text-align: center;
        }
        .form-container {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .transaction-container {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            position: relative;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        label {
            display: block;
            margin-top: 10px;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #2980b9;
        }
        .remove-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            position: absolute;
            top: 15px;
            right: 15px;
        }
        .remove-btn:hover {
            background-color: #c0392b;
        }
        .transaction-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .result-container {
            margin-top: 20px;
            background-color: #f0f7ff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            color: #2c3e50;
            margin-top: 0;
        }
        h3 {
            margin: 0;
        }
        .result-item {
            margin-bottom: 10px;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <h1>Financial Transaction Processor</h1>
    
    <div class="form-container">
        <form id="transactionForm" method="post">
            <div id="transactions">
                <div class="transaction-container" id="transaction-1">
                    <div class="transaction-header">
                        <h3>Transaction 1</h3>
                        <!-- No remove button for first transaction -->
                    </div>
                    <label>Amount (AED):
                        <input type="number" name="amount[]" step="0.01" min="0" required>
                    </label>
                    <label>Type:
                        <select name="type[]" required>
                            <option value="credit">Credit</option>
                            <option value="debit">Debit</option>
                        </select>
                    </label>
                    <label>Category:
                        <select name="category[]" required>
                            <option value="goods">Goods</option>
                            <option value="services">Services</option>
                            <option value="investment">Investment</option>
                        </select>
                    </label>
                </div>
            </div>
            
            <button type="button" id="addTransaction">Add Another Transaction</button>
            
            <label>Time Period (Years):
                <input type="number" name="years" step="0.01" min="0" required value="1">
            </label>
            
            <label>Interest Rate:
                <input type="number" name="interestRate" step="0.01" min="0" required value="0.05">
            </label>
            
            <label>VAT Rate:
                <input type="number" name="vatRate" step="0.01" min="0" required value="0.05">
            </label>
            
            <button type="submit">Process Transactions</button>
        </form>
    </div>
    
    <?php if ($showResults && $result): ?>
    <div class="result-container">
        <h2>Results</h2>
        <div class="result-item"><strong>Net Balance (post-VAT):</strong> <?php echo number_format($result['netBalance'], 2); ?> AED</div>
        <div class="result-item"><strong>Final Balance with Interest:</strong> <?php echo number_format($result['finalBalance'], 2); ?> AED</div>
        
        <h3>Category Breakdown:</h3>
        <?php foreach ($result['categoryBreakdown'] as $category => $total): ?>
            <div class="result-item"><strong><?php echo ucfirst($category); ?>:</strong> <?php echo number_format($total, 2); ?> AED</div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let transactionCount = 1;
            
            // Function to add a new transaction
            document.getElementById('addTransaction').addEventListener('click', function() {
                transactionCount++;
                
                const transactionDiv = document.createElement('div');
                transactionDiv.className = 'transaction-container';
                transactionDiv.id = 'transaction-' + transactionCount;
                transactionDiv.innerHTML = `
                    <div class="transaction-header">
                        <h3>Transaction ${transactionCount}</h3>
                        <button type="button" class="remove-btn" onclick="removeTransaction(${transactionCount})">Remove</button>
                    </div>
                    <label>Amount (AED):
                        <input type="number" name="amount[]" step="0.01" min="0" required>
                    </label>
                    <label>Type:
                        <select name="type[]" required>
                            <option value="credit">Credit</option>
                            <option value="debit">Debit</option>
                        </select>
                    </label>
                    <label>Category:
                        <select name="category[]" required>
                            <option value="goods">Goods</option>
                            <option value="services">Services</option>
                            <option value="investment">Investment</option>
                        </select>
                    </label>
                `;
                
                document.getElementById('transactions').appendChild(transactionDiv);
            });
            
            // Make the remove function globally available
            window.removeTransaction = function(id) {
                const transactionElement = document.getElementById('transaction-' + id);
                if (transactionElement) {
                    transactionElement.remove();
                }
            };
        });
    </script>
</body>
</html>
