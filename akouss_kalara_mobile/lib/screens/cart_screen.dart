import 'package:flutter/material.dart';

import '../services/api_service.dart';
import '../services/auth_service.dart';
import 'login_screen.dart';

class CartScreen extends StatefulWidget {
  const CartScreen({super.key});

  @override
  State<CartScreen> createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  List<Map<String, dynamic>> _items = [];
  double _total = 0;

  bool _isLoading = true;
  String? _errorMessage;
  int? _updatingLineId;
  bool _isCreatingOrder = false;
  bool _isInitiatingPayment = false;

  @override
  void initState() {
    super.initState();
    _loadCart();
  }

  Future<void> _loadCart() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final token = await AuthService.getToken();

      if (!mounted) return;

      if (token == null || token.isEmpty) {
        setState(() {
          _items = [];
          _total = 0;
          _isLoading = false;
        });

        return;
      }

      final data = await ApiService.getCart(token);

      final lignes = data['lignes'];

      final items = lignes is List
          ? lignes.map((item) => Map<String, dynamic>.from(item)).toList()
          : <Map<String, dynamic>>[];

      final total = double.tryParse(data['total']?.toString() ?? '0') ?? 0;

      if (!mounted) return;

      setState(() {
        _items = items;
        _total = total;
        _isLoading = false;
        _updatingLineId = null;
      });
    } catch (e) {
      if (!mounted) return;

      setState(() {
        _errorMessage = e.toString();
        _isLoading = false;
        _updatingLineId = null;
      });
    }
  }

  Future<void> _openLogin() async {
    final result = await Navigator.push(
      context,
      MaterialPageRoute(builder: (context) => const LoginScreen()),
    );

    if (result == true) {
      await _loadCart();
    }
  }

  Future<void> _changeQuantity(
    Map<String, dynamic> item,
    int newQuantity,
  ) async {
    if (newQuantity < 1) {
      return;
    }

    final lineId = int.tryParse(item['id']?.toString() ?? '');

    if (lineId == null) {
      return;
    }

    final token = await AuthService.getToken();

    if (token == null || token.isEmpty) {
      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Votre session a expiré. Veuillez vous reconnecter.'),
        ),
      );

      return;
    }

    setState(() {
      _updatingLineId = lineId;
    });

    try {
      await ApiService.updateCartItem(
        token: token,
        lineId: lineId,
        quantity: newQuantity,
      );

      await _loadCart();
    } catch (e) {
      if (!mounted) return;

      setState(() {
        _updatingLineId = null;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    }
  }

  Future<void> _removeCartItem(Map<String, dynamic> item) async {
    final lineId = int.tryParse(item['id']?.toString() ?? '');

    if (lineId == null) {
      return;
    }

    final token = await AuthService.getToken();

    if (token == null || token.isEmpty) {
      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Votre session a expiré. Veuillez vous reconnecter.'),
        ),
      );

      return;
    }

    setState(() {
      _updatingLineId = lineId;
    });

    try {
      await ApiService.removeCartItem(token: token, lineId: lineId);

      await _loadCart();
    } catch (e) {
      if (!mounted) return;

      setState(() {
        _updatingLineId = null;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    }
  }

  Future<void> _createOrder() async {
    final token = await AuthService.getToken();

    if (token == null || token.isEmpty) {
      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Votre session a expiré. Veuillez vous reconnecter.'),
        ),
      );

      return;
    }

    setState(() {
      _isCreatingOrder = true;
    });

    try {
      final data = await ApiService.createOrder(
        token: token,
        deliveryMode: 'domicile',
      );

      if (!mounted) return;

      final commandeId = int.tryParse(data['commande_id']?.toString() ?? '');

      final montantTotal = data['montant_total'];

      setState(() {
        _isCreatingOrder = false;
      });

      await _loadCart();

      if (!mounted) return;

      if (commandeId == null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Commande créée. Total : '
              '${_formatPrice(montantTotal)}',
            ),
          ),
        );

        return;
      }

      await _showPaymentDialog(
        commandeId: commandeId,
        montantTotal: montantTotal,
      );
    } catch (e) {
      if (!mounted) return;

      setState(() {
        _isCreatingOrder = false;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    }
  }

  Future<void> _showPaymentDialog({
    required int commandeId,
    required dynamic montantTotal,
  }) async {
    String fournisseur = 'mtn_momo';
    final telephoneController = TextEditingController();

    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: const Text('Choisir le mode de paiement'),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Commande #$commandeId',
                      style: const TextStyle(fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 6),
                    Text('Montant : ${_formatPrice(montantTotal)}'),
                    const SizedBox(height: 20),

                    const Text(
                      'Mode de paiement',
                      style: TextStyle(fontWeight: FontWeight.w600),
                    ),
                    const SizedBox(height: 8),

                    RadioGroup<String>(
                      groupValue: fournisseur,
                      onChanged: (value) {
                        if (_isInitiatingPayment || value == null) {
                          return;
                        }

                        setDialogState(() {
                          fournisseur = value;
                        });
                      },
                      child: Column(
                        children: [
                          RadioListTile<String>(
                            value: 'mtn_momo',
                            contentPadding: EdgeInsets.zero,
                            title: const Text('MTN Mobile Money'),
                            subtitle: const Text('Paiement par MTN MoMo'),
                          ),
                          RadioListTile<String>(
                            value: 'orange_money',
                            contentPadding: EdgeInsets.zero,
                            title: const Text('Orange Money'),
                            subtitle: const Text('Paiement par Orange Money'),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 8),

                    TextField(
                      controller: telephoneController,
                      enabled: !_isInitiatingPayment,
                      keyboardType: TextInputType.phone,
                      textInputAction: TextInputAction.done,
                      decoration: const InputDecoration(
                        labelText: 'Numéro de téléphone',
                        hintText: '6XXXXXXXX',
                        prefixIcon: Icon(Icons.phone_android),
                        border: OutlineInputBorder(),
                      ),
                    ),

                    const SizedBox(height: 16),

                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.orange.shade300),
                        color: Colors.orange.shade50,
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Icon(
                            Icons.science_outlined,
                            color: Colors.orange.shade800,
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'MODE SIMULATION — DÉVELOPPEMENT\\n'
                              'Aucun paiement réel ne sera effectué. '
                              'Cette simulation permet de tester le '
                              'parcours de paiement avant l’intégration '
                              'ARITED.',
                              style: TextStyle(
                                fontSize: 12,
                                color: Colors.orange.shade900,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: _isInitiatingPayment
                      ? null
                      : () {
                          Navigator.pop(dialogContext);
                        },
                  child: const Text('Plus tard'),
                ),
                OutlinedButton.icon(
                  onPressed: _isInitiatingPayment
                      ? null
                      : () async {
                          await _handlePaymentSimulation(
                            dialogContext: dialogContext,
                            commandeId: commandeId,
                            fournisseur: fournisseur,
                            telephone: telephoneController.text.trim(),
                            statut: 'echec',
                            setDialogState: setDialogState,
                          );
                        },
                  icon: const Icon(Icons.cancel_outlined),
                  label: const Text('Simuler échec'),
                ),
                ElevatedButton.icon(
                  onPressed: _isInitiatingPayment
                      ? null
                      : () async {
                          await _handlePaymentSimulation(
                            dialogContext: dialogContext,
                            commandeId: commandeId,
                            fournisseur: fournisseur,
                            telephone: telephoneController.text.trim(),
                            statut: 'succes',
                            setDialogState: setDialogState,
                          );
                        },
                  icon: _isInitiatingPayment
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.check_circle_outline),
                  label: Text(
                    _isInitiatingPayment ? 'Simulation...' : 'Simuler succès',
                  ),
                ),
              ],
            );
          },
        );
      },
    );

    telephoneController.dispose();
  }

  Future<void> _handlePaymentSimulation({
    required BuildContext dialogContext,
    required int commandeId,
    required String fournisseur,
    required String telephone,
    required String statut,
    required void Function(void Function()) setDialogState,
  }) async {
    if (telephone.isEmpty) {
      ScaffoldMessenger.of(dialogContext).showSnackBar(
        const SnackBar(
          content: Text('Veuillez saisir votre numéro de téléphone.'),
        ),
      );

      return;
    }

    setDialogState(() {
      _isInitiatingPayment = true;
    });

    final success = await _simulatePayment(
      commandeId: commandeId,
      fournisseur: fournisseur,
      telephone: telephone,
      statut: statut,
    );

    if (!mounted) return;

    if (!success) {
      setDialogState(() {
        _isInitiatingPayment = false;
      });
    }
  }

  Future<bool> _simulatePayment({
    required int commandeId,
    required String fournisseur,
    required String telephone,
    required String statut,
  }) async {
    final token = await AuthService.getToken();

    if (token == null || token.isEmpty) {
      if (!mounted) return false;

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Votre session a expiré. Veuillez vous reconnecter.'),
        ),
      );

      return false;
    }

    try {
      final data = await ApiService.simulerPaiement(
        token: token,
        orderId: commandeId,
        provider: fournisseur,
        phone: telephone,
        status: statut,
      );

      if (!mounted) return false;

      final referenceId = data['reference_id']?.toString() ?? '';

      final message = data['message']?.toString() ?? 'Simulation terminée.';

      final statutPaiement = data['statut_paiement']?.toString() ?? '';

      final statutCommande = data['statut_commande']?.toString() ?? '';

      Navigator.of(context).pop();

      await _loadCart();

      if (!mounted) return false;

      await _showPaymentResultDialog(
        fournisseur: fournisseur,
        commandeId: commandeId,
        referenceId: referenceId,
        message: message,
        paymentUrl: null,
        simulation: true,
        statutPaiement: statutPaiement,
        statutCommande: statutCommande,
      );

      return true;
    } catch (e) {
      if (!mounted) return false;

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );

      return false;
    }
  }

  Future<bool> _initiatePayment({
    required int commandeId,
    required String fournisseur,
    required String telephone,
  }) async {
    final token = await AuthService.getToken();

    if (token == null || token.isEmpty) {
      if (!mounted) return false;

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Votre session a expiré. Veuillez vous reconnecter.'),
        ),
      );

      return false;
    }

    try {
      final data = await ApiService.initierPaiement(
        token: token,
        orderId: commandeId,
        provider: fournisseur,
        phone: telephone,
      );

      if (!mounted) return false;

      final referenceId = data['reference_id']?.toString() ?? '';

      final message = data['message']?.toString() ?? 'Paiement initié.';

      Navigator.of(context).pop();

      await _showPaymentResultDialog(
        fournisseur: fournisseur,
        commandeId: commandeId,
        referenceId: referenceId,
        message: message,
        paymentUrl: data['url_paiement']?.toString(),
      );

      return true;
    } catch (e) {
      if (!mounted) return false;

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );

      return false;
    }
  }

  Future<void> _showPaymentResultDialog({
    required String fournisseur,
    required int commandeId,
    required String referenceId,
    required String message,
    String? paymentUrl,
    bool simulation = false,
    String statutPaiement = '',
    String statutCommande = '',
  }) async {
    final providerName = fournisseur == 'mtn_momo'
        ? 'MTN Mobile Money'
        : 'Orange Money';

    final paiementReussi = statutPaiement == 'succes';

    await showDialog<void>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(
            simulation ? 'Résultat de la simulation' : 'Paiement initié',
          ),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(
                  paiementReussi
                      ? Icons.check_circle
                      : simulation
                      ? Icons.cancel
                      : Icons.check_circle_outline,
                  size: 56,
                ),
                const SizedBox(height: 16),

                Text(
                  message,
                  style: const TextStyle(fontWeight: FontWeight.w500),
                ),

                const SizedBox(height: 12),

                Text(
                  'Commande : #$commandeId',
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),

                const SizedBox(height: 6),

                Text('Fournisseur : $providerName'),

                if (simulation) ...[
                  const SizedBox(height: 6),
                  Text('Statut paiement : $statutPaiement'),
                  const SizedBox(height: 6),
                  Text('Statut commande : $statutCommande'),
                ],

                if (referenceId.isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Text('Référence : $referenceId'),
                ],

                if (paymentUrl != null && paymentUrl.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Text(
                    'Une page de paiement est disponible '
                    'pour cette transaction.',
                    style: TextStyle(color: Colors.grey.shade700),
                  ),
                ],

                if (simulation) ...[
                  const SizedBox(height: 16),
                  Text(
                    'Cette opération est une simulation de '
                    'développement. Aucun argent réel n’a été débité.',
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
                  ),
                ],
              ],
            ),
          ),
          actions: [
            ElevatedButton(
              onPressed: () {
                Navigator.pop(context);
              },
              child: const Text('OK'),
            ),
          ],
        );
      },
    );
  }

  String _formatPrice(dynamic value) {
    final price = double.tryParse(value?.toString() ?? '0') ?? 0;

    return '${price.toStringAsFixed(0)} FCFA';
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(child: _buildContent());
  }

  Widget _buildContent() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline, size: 48),
              const SizedBox(height: 12),
              const Text(
                'Impossible de charger le panier.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              Text(_errorMessage!, textAlign: TextAlign.center),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: _loadCart,
                icon: const Icon(Icons.refresh),
                label: const Text('Réessayer'),
              ),
            ],
          ),
        ),
      );
    }

    return FutureBuilder<bool>(
      future: AuthService.isLoggedIn(),
      builder: (context, snapshot) {
        final isLoggedIn = snapshot.data ?? false;

        if (!isLoggedIn) {
          return _buildNotLoggedIn();
        }

        if (_items.isEmpty) {
          return _buildEmptyCart();
        }

        return _buildCart();
      },
    );
  }

  Widget _buildNotLoggedIn() {
    return Padding(
      padding: const EdgeInsets.all(20),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.shopping_cart_outlined,
            size: 80,
            color: Theme.of(context).colorScheme.primary,
          ),
          const SizedBox(height: 20),
          const Text(
            'Votre panier',
            style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Text(
            'Connectez-vous pour accéder à votre panier.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 16, color: Colors.grey.shade700),
          ),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _openLogin,
              icon: const Icon(Icons.login),
              label: const Text('Se connecter'),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyCart() {
    return RefreshIndicator(
      onRefresh: _loadCart,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(20),
        children: [
          const SizedBox(height: 100),
          Icon(
            Icons.shopping_cart_outlined,
            size: 80,
            color: Colors.grey.shade500,
          ),
          const SizedBox(height: 20),
          const Text(
            'Votre panier est vide.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          Text(
            'Ajoutez des livres depuis le catalogue.',
            textAlign: TextAlign.center,
            style: TextStyle(color: Colors.grey.shade700),
          ),
        ],
      ),
    );
  }

  Widget _buildCart() {
    return RefreshIndicator(
      onRefresh: _loadCart,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 30),
        children: [
          const Text(
            'Mon panier',
            style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 20),
          ..._items.map(_buildCartItem),
          const SizedBox(height: 12),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(18),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Total',
                    style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                  ),
                  Text(
                    _formatPrice(_total),
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          SizedBox(
            height: 52,
            child: ElevatedButton.icon(
              onPressed: _isCreatingOrder ? null : _createOrder,
              icon: _isCreatingOrder
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.payment),
              label: Text(
                _isCreatingOrder
                    ? 'Création de la commande...'
                    : 'Passer la commande',
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCartItem(Map<String, dynamic> item) {
    final title = item['titre']?.toString() ?? 'Livre';
    final author = item['auteur']?.toString() ?? '';
    final seller = item['vendeur_nom']?.toString() ?? '';
    final price = item['prix'];

    final quantity = int.tryParse(item['quantite']?.toString() ?? '1') ?? 1;

    final type = item['type']?.toString() ?? '';

    final lineId = int.tryParse(item['id']?.toString() ?? '');

    final isUpdating = lineId != null && _updatingLineId == lineId;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 70,
              height: 90,
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.primaryContainer,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(
                Icons.menu_book,
                size: 38,
                color: Theme.of(context).colorScheme.onPrimaryContainer,
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 17,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  if (author.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      author,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(color: Colors.grey.shade700),
                    ),
                  ],
                  const SizedBox(height: 6),
                  Text(
                    _formatPrice(price),
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                  ),
                  if (seller.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      'Vendeur : $seller',
                      style: TextStyle(
                        fontSize: 13,
                        color: Colors.grey.shade600,
                      ),
                    ),
                  ],
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      const Text(
                        'Quantité',
                        style: TextStyle(fontWeight: FontWeight.w600),
                      ),
                      const SizedBox(width: 8),
                      IconButton(
                        onPressed: isUpdating || quantity <= 1
                            ? null
                            : () => _changeQuantity(item, quantity - 1),
                        icon: const Icon(Icons.remove_circle_outline),
                        tooltip: 'Diminuer',
                      ),
                      if (isUpdating)
                        const SizedBox(
                          width: 24,
                          height: 24,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      else
                        Text(
                          quantity.toString(),
                          style: const TextStyle(
                            fontSize: 17,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      IconButton(
                        onPressed: isUpdating
                            ? null
                            : () => _changeQuantity(item, quantity + 1),
                        icon: const Icon(Icons.add_circle_outline),
                        tooltip: 'Augmenter',
                      ),
                    ],
                  ),
                  Text(
                    type == 'physique' ? 'Livre physique' : 'Livre numérique',
                    style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
                  ),
                  const SizedBox(height: 8),
                  Align(
                    alignment: Alignment.centerRight,
                    child: TextButton.icon(
                      onPressed: isUpdating
                          ? null
                          : () => _removeCartItem(item),
                      icon: const Icon(Icons.delete_outline, size: 20),
                      label: const Text('Supprimer'),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
